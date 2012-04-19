<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/exporttbl.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		$tableName = v($_REQUEST['table']);
		if ($tableName) {
			$replace = array('TABLENAME' => $tableName);
			echo view('exporttbl', $replace);
		}
		else
			echo view('invalid_request', array());
	}

?>