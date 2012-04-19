<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/showinfo.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	$type = $_REQUEST["id"];
	if ($type == 'table' || $type == 'view') {
		$_REQUEST["id"] = 'table';
		$_REQUEST["query"] = $_REQUEST["name"];
		unset($_REQUEST['name']);
		Session::del('select');
		include('query.php');
	} else {
		function processRequest(&$db) {
			$extraMsg = '';

			$type = $_REQUEST["id"];
			$name = $_REQUEST["name"];
			
			$cmd = $db->getCreateCommand($type, $name);
			$cmd = sanitizeCreateCommand($type, $cmd);
			//$tm = $db->getQueryTime();

			$replace = array('TYPE' => $type,
								'NAME' => $name,
								'COMMAND' => $cmd,
								//'TIME' => $tm,
								//'SQL' => $sql
								//'MESSAGE' => $extraMsg
							);

			echo view('showinfo', $replace);
		}
	}
?>