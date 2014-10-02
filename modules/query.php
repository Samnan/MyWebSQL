<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/query.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

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
			else
				createResultGrid($db);
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

		Session::del('select', 'can_limit');
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
					Session::set('select', 'limit', true);
					$limit = $db->getLimit( $record_limit );
					$query .= $limit;
				}
			}
		}

		return $query;
	}
?>