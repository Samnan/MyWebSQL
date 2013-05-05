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
				else if ($info['setvar'] == TRUE)
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
			Session::del('select', 'has_limit');
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
			Session::set('select', 'has_limit', true);
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
		if ($query_type['result'] == TRUE) {
			Session::del('select', 'table');
			Session::del('select', 'limit');
			Session::del('select', 'has_limit');
			Session::del('select', 'page');
			Session::del('select', 'count');
			
			Session::set('select', 'query', $query);
			Session::set('select', 'has_limit', $query_type['has_limit'] == TRUE);
		}
		
		// try to find limit clause in the query. If one is not applied, apply now
		/*$regExpr = "/limit [0-9]+((\s)*,(\s)*[0-9]+)/";
		preg_match($regExpr, $query, $matches);
		if (isset($matches[1]))
		{
			//$query = str_replace($matches[1], "", $query);
		}
		else
		{
			$limitStart = v($_REQUEST['ls']) && ctype_digit(v($_REQUEST['ls'])) ? v($_REQUEST['ls']) : 0;
			$limitEnd = v($_REQUEST['le']) && ctype_digit(v($_REQUEST['le'])) ? v($_REQUEST['le']) : MAX_RECORD_TO_DISPLAY;
			$_SESSION['limit_start'] = $limitStart;
			$_SESSION['limit_end'] = $limitEnd;
			$_SESSION['limit_applied'] = 1;
		}*/
	
		return $query;
	}
?>