<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/databases.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		$folder = $db->name();

		$data = array();

		if (v($_REQUEST["id"]) == 'batch') {
			$stats = array();

			$stats['drop'] = drop_objects( $db );

			$replace = array();
			$data['stats'] = $stats;
			$data['objects'] = $db->getDatabases();
			echo view( array($folder.'/databases', 'databases'), $replace, $data);
		} else {
			$replace = array();
			$data['objects'] = $db->getDatabases();
			echo view( array($folder.'/databases', 'databases'), $replace, $data);
		}
	}

	function drop_objects( &$db ) {
		$status = array('success' => 0, 'errors' => 0);
		$types = $db->getObjectTypes();

		foreach(v($_POST['databases'], array()) as $database) {
			if ( v($_POST['dropcmd']) == "on" ) {
				if ($db->dropObject($database, 'database'))
					$status['success']++;
				else
					$status['errors']++;
			}
		}

		return $status;
	}

?>