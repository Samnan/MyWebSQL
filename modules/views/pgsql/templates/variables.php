<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/views/pgsql/templates/variables.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function parseVariables(&$db) {
		$vars = array();
		while($row = $db->fetchRow())
		{
			switch($row[0]) {
				case "server_version":
					$vars['SERVER_VERSION'] = $row[1];
				case "client_encoding":
					$vars['CLIENT_CHARSET'] = $row[1]; break;
				case "server_encoding":
					$vars['SERVER_CHARSET'] = $row[1];
					$vars['DATABASE_CHARSET'] = $row[1];
					$vars['RESULT_CHARSET'] = $row[1]; break;
			}
		}
		$vars['SERVER_NAME'] = 'PostgreSQL';
		$vars['SERVER_COMMENT'] = Session::get('db', 'version_comment');
		return $vars;
	}
?>
