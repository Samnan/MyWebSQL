<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/query.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        https://github.com/Samnan/MyWebSQL
 * @license    https://github.com/Samnan/MyWebSQL/license
 */

	/**
	 * Validates dangerous SQL queries for safety
	 * - Blocks all TRUNCATE commands completely
	 * - Blocks DROP TABLE commands completely
	 * - Requires WHERE clause for DELETE commands
	 * - Other queries are allowed without restriction
	 * 
	 * @param string $query The SQL query to validate
	 * @return array Returns ['valid' => bool, 'error' => string or null]
	 */
	function validateDeleteQuery($query) {
		// Normalize query: trim whitespace and convert to uppercase for comparison
		$query_upper = strtoupper(trim($query));
		
		// Extract first word to identify command type
		$first_word = strtok($query_upper, ' ');
		
		// Check if query starts with TRUNCATE command - always block
		if ($first_word === 'TRUNCATE') {
			// TRUNCATE is completely disabled for security
			return [
				'valid' => false,
				'error' => __('TRUNCATE queries are not allowed. Use DELETE with a WHERE clause instead.')
			];
		}
		
		// Check if query starts with DROP command - only block DROP TABLE
		if ($first_word === 'DROP') {
			// Get second word to check if it's DROP TABLE
			$second_word = strtok(' ');
			if ($second_word === 'TABLE') {
				// DROP TABLE is blocked for security
				return [
					'valid' => false,
					'error' => __('DROP TABLE commands are not allowed. Use DELETE with a WHERE clause instead.')
				];
			}
		}
		
		// Check if this is a DELETE query that requires WHERE clause validation
		if ($first_word === 'DELETE') {
			// Use regex to find WHERE keyword as a whole word (not part of another word)
			if (!preg_match('/\bWHERE\b/i', $query)) {
				// WHERE clause not found - DELETE without WHERE is dangerous
				return [
					'valid' => false,
					'error' => __('DELETE query must contain a WHERE clause to specify which records to delete')
				];
			}
			
			// Check for forced-true conditions that don't actually filter rows
			// Patterns like WHERE 1=1, WHERE 2=2, WHERE 'a'='a' are always true and bypass the WHERE requirement
			// Allow only conditions that reference actual column names, not literal values compared to themselves
			if (preg_match('/\bWHERE\s+(\d+\s*[=<>!]+\s*\d+|\'[^\']*\'\s*=\s*\'[^\']*\'|"[^"]*"\s*=\s*"[^"]*"|(true|false))\b/i', $query)) {
				// Forced-true pattern detected - this bypasses the WHERE clause requirement
				return [
					'valid' => false,
					'error' => __('WHERE clause must contain a meaningful condition that references actual columns, not forced true/false values like "WHERE 1=1" or "WHERE 2=2".')
				];
			}
		}
		
		// Query passed all safety checks
		return ['valid' => true, 'error' => null];
	}

	function processRequest(&$db) {
		// first time query will be from request, then we will get it from session (applying limit scenario)
		$table_select = v($_REQUEST["id"]) == 'table' ? true: false;

		if ($table_select)
			$query = selectFromTable($db);
		else
			$query = simpleQuery($db);

		if (!$query) {
			createErrorGrid($db, $query);
			return;
		}

		// Validate dangerous queries if safety checks are enabled in configuration
		if (SAFE_QUERIES) {
			// Call validation function to check for DELETE, TRUNCATE, and DROP commands
			$validation_result = validateDeleteQuery($query);
			
			// If validation failed, display error and prevent query execution
			if (!$validation_result['valid']) {
				// Set error message and render error grid to user
				$db->error = $validation_result['error'];
				createErrorGrid($db, $query);
				return;
			}
		}

		loadDbVars($db);
		if ($db->query($query)) {
			if (!$db->hasResult()) {
				$info = getCommandInfo($query);
				if ($info['dbAltered'])
					Session::set('db', 'altered', true);
				else if ($info['setvar'] == TRUE && is_scalar($info['variable']) && is_scalar($info['value']))
					setDbVar( $info['variable'], $info['value'] );
				createInfoGrid($db);
			}
			else {
				// if it is a data result set, show it as result grid, otherwise in simple grid layout
				$query_type = getQueryType($query);
				if ($query_type['can_limit'])
					createResultGrid($db);
				else
					createSimpleGrid($db, __('Query') . ': ' . $query);
			}
		}
		else
			createErrorGrid($db, $query);
	}

	function selectFromTable(&$db) {
		$query = '';
		$record_limit = Options::get('res-max-count', MAX_RECORD_TO_DISPLAY);

		$page = v($_REQUEST['name']);
		if ($page) {  // subsequent page requests from a table
			$limit_applied = Session::get('select', 'limit');
			if (!ctype_digit($page) | $page < 1 || !$limit_applied)
				return '';

			$query = Session::get('select', 'query');
			$table = Session::get('select', 'table');
			$count = Session::get('select', 'count');
			$query_type = getQueryType($query);
			if ($query_type['result'] == FALSE || !$table || !ctype_digit($count))
				return '';  // page requested but not a valid table query

			$total_pages = ceil($count / $record_limit);
			if ($total_pages < $page)
				return '';

			Session::set('select', 'page', $page);
			$limit = $db->getLimit( $record_limit, ($page-1)*$record_limit );
			$query .= $limit;
		} else {  // query from table first time
			Session::del('select', 'table');
			Session::del('select', 'can_limit');
			Session::del('select', 'limit');
			Session::del('select', 'page');
			Session::del('select', 'count');

			$table = v($_REQUEST["query"]);
			$query = 'select * from ' . $db->quote($table);
			Session::set('select', 'query', $query);
			Session::set('select', 'table', $table);
			$sql = 'select count(*) as count_rec from ' . $db->quote($table);
			if (!$db->query($sql))
				return '';
			$row = $db->fetchRow();
			$count = $row[0];
			Session::set('select', 'count', $count);
			Session::set('select', 'page', 1);
			Session::set('select', 'can_limit', true);
			if ($count > $record_limit) {
				Session::set('select', 'limit', true);
				$limit = $db->getLimit( $record_limit );
				$query .= $limit;
			}
		}

		return $query;
	}

	function simpleQuery(&$db) {
		$query = v($_REQUEST["query"]);
		if (!$query)
			$query = Session::get('select', 'query');  // try to load from session

		if (!$query)
			return '';

		// see if user is restricted to a list of databases by configuration
		// if yes, then disallow db use queries
		// it's still possible that the command can contain db prefixes, which will override the db selection
		//$info = getCommandInfo($query);
		//if ($info['dbChanged'])
		//	return '';

		$query_type = getQueryType($query);
		if ($query_type['result'] == FALSE)
			return $query;
		
		// only apply limit/sort to select queries with results
		if ($query_type['can_limit'] == FALSE) {

			return $query;
		}

		Session::set('select', 'can_limit', $query_type['can_limit'] == TRUE);

		if ( v($_REQUEST["id"]) == 'sort' ) {
			$field = v($_REQUEST['name']);
			if ($field) {
				$query = sortQuery($query, ctype_digit($field) ? $field : $db->quote($field));
			}
			// clear pagination if sorting is changed
			Session::set('select', 'page', 1);
		}

		// save order clause with query in session, required for pagination
		Session::set('select', 'query', $query);

		// try to find limit clause in the query. If one is not applied, apply now
		// only either sort or pagination request can come at a time
		if( !$query_type['has_limit'] && v($_REQUEST["id"]) != 'sort' ) {
			$record_limit = Options::get('res-max-count', MAX_RECORD_TO_DISPLAY);
			$page = v($_REQUEST['name']);

			if ( $page ) {
				$limit_applied = Session::get('select', 'limit');
				if (!ctype_digit($page) | $page < 1 || !$limit_applied)
					return $query;

				$count = Session::get('select', 'count');
				$total_pages = ceil($count / $record_limit);
				if ($total_pages < $page)
					return $query;

				Session::set('select', 'page', $page);
				$limit = $db->getLimit( $record_limit, ($page-1)*$record_limit );
				$query .= $limit;
			} else {
				Session::del('select', 'table');
				Session::del('select', 'limit');
				Session::del('select', 'page');
				Session::del('select', 'count');
				Session::del('select', 'sort');
				Session::del('select', 'sortcol');

				if (!$db->query($query))
					return $query;
				$count = $db->numRows();
				if ($count > $record_limit) {
					Session::set('select', 'count', $count);
					Session::set('select', 'page', 1);
					Session::set('select', 'limit', 1);
					$limit = $db->getLimit( $record_limit );
					$query .= $limit;
				}
			}
		}

		return $query;
	}
?>