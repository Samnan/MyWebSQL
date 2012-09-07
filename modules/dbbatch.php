<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/dbbatch.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		if (v($_REQUEST["id"]) == 'batch') {
			$stats = array();
			$commands = array();
			
			// generate commands first before doing drop operations
			if ( v($_POST['command']) != '' )
				$commands = generate_commands( $db, v($_POST['command']) );
			
			if ( v($_POST['dropcmd']) == 'on' )
				$stats['drop'] = drop_objects( $db );
			else {
				if ( v($_POST['old_prefix']) != '' )
					$stats['delprefix'] = remove_prefix( $db, v($_POST['old_prefix']) );
				if ( v($_POST['new_prefix']) != '' )
					$stats['addprefix'] = add_prefix( $db, v($_POST['new_prefix']) );
			}
			
			$replace = array();
			$data = array('stats' => $stats, 'queries' => $commands);
			echo view('dbbatch_results', $replace, $data);
		} else {
			$db_tables = $db->getTables();
			$db_views = $db->getViews();
			$db_procedures = $db->getProcedures();
			$db_functions = $db->getFunctions();
			$db_triggers = $db->getTriggers();
			$db_events = $db->getEvents();
	
			$replace = array('TABLELIST' => json_encode($db_tables),
							'VIEWLIST' => json_encode($db_views),
							'PROCLIST' => json_encode($db_procedures),
							'FUNCLIST' => json_encode($db_functions),
							'TRIGGERLIST' => json_encode($db_triggers),
							'EVENTLIST' => json_encode($db_events)
							);
			echo view('dbbatch', $replace);
		}
	}

	function drop_objects( &$db ) {
		$status = array('success' => 0, 'errors' => 0);
		
		foreach(v($_POST['tables'], array()) as $table) {
			if ($db->dropObject($table, 'table'))
				$status['success']++;
			else
				$status['errors']++;
		}
		foreach(v($_POST['views'], array()) as $table) {
			if ($db->dropObject($table, 'table'))
				$status['success']++;
			else
				$status['errors']++;
		}
		foreach(v($_POST['procedures'], array()) as $table) {
			if ($db->dropObject($table, 'table'))
				$status['success']++;
			else
				$status['errors']++;
		}
		foreach(v($_POST['functions'], array()) as $table) {
			if ($db->dropObject($table, 'table'))
				$status['success']++;
			else
				$status['errors']++;
		}
		foreach(v($_POST['triggers'], array()) as $table) {
			if ($db->dropObject($table, 'table'))
				$status['success']++;
			else
				$status['errors']++;
		}
		
		return $status;
	}
	function remove_prefix( &$db, $prefix ) {
		$status = array('success' => 0, 'errors' => 0);
		
		foreach(v($_POST['tables'], array()) as $table) {
			if ( substr($table, 0, strlen($prefix)) == $prefix ) {
				$new_name = substr($table, strlen($prefix));
				if ($db->renameObject($table, 'table', $new_name))
					$status['success']++;
				else
					$status['errors']++;
			}
		}
		foreach(v($_POST['views'], array()) as $table) {
			if ( substr($table, 0, strlen($prefix)) == $prefix ) {
				$new_name = substr($table, strlen($prefix));
				if ($db->renameObject($table, 'view', $new_name))
					$status['success']++;
				else
					$status['errors']++;
			}
		}
		foreach(v($_POST['procedures'], array()) as $table) {
			if ( substr($table, 0, strlen($prefix)) == $prefix ) {
				$new_name = substr($table, strlen($prefix));
				if ($db->renameObject($table, 'procedure', $new_name))
					$status['success']++;
				else
					$status['errors']++;
			}
		}
		foreach(v($_POST['functions'], array()) as $table) {
			if ( substr($table, 0, strlen($prefix)) == $prefix ) {
				$new_name = substr($table, strlen($prefix));
				if ($db->renameObject($table, 'function', $new_name))
					$status['success']++;
				else
					$status['errors']++;
			}
		}
		foreach(v($_POST['triggers'], array()) as $table) {
			if ( substr($table, 0, strlen($prefix)) == $prefix ) {
				$new_name = substr($table, strlen($prefix));
				if ($db->renameObject($table, 'trigger', $new_name))
					$status['success']++;
				else
					$status['errors']++;
			}
		}
		
		return $status;
	}
	
	function add_prefix( &$db, $prefix ) {
		$status = array('success' => 0, 'errors' => 0);
		
		foreach(v($_POST['tables'], array()) as $table) {
			if ($db->renameObject($table, 'table', $prefix . $table))
				$status['success']++;
			else
				$status['errors']++;
		}
		foreach(v($_POST['views'], array()) as $table) {
			if ($db->renameObject($table, 'view', $prefix . $table))
				$status['success']++;
			else
				$status['errors']++;
		}
		foreach(v($_POST['procedures'], array()) as $table) {
			if ($db->renameObject($table, 'procedure', $prefix . $table))
				$status['success']++;
			else
				$status['errors']++;
		}
		foreach(v($_POST['functions'], array()) as $table) {
			if ($db->renameObject($table, 'function', $prefix . $table))
				$status['success']++;
			else
				$status['errors']++;
		}
		foreach(v($_POST['triggers'], array()) as $table) {
			if ($db->renameObject($table, 'trigger', $prefix . $table))
				$status['success']++;
			else
				$status['errors']++;
		}
		
		return $status;
	}
	
	function generate_commands( &$db, $command ) {
		$commands = array();
		
		foreach(v($_POST['tables'], array()) as $table) {
			$commands[] = $command . ' ' . $table;
		}
		foreach(v($_POST['views'], array()) as $table) {
			$commands[] = $command . ' ' . $table;
		}
		foreach(v($_POST['procedures'], array()) as $table) {
			$commands[] = $command . ' ' . $table;
		}
		foreach(v($_POST['functions'], array()) as $table) {
			$commands[] = $command . ' ' . $table;
		}
		foreach(v($_POST['triggers'], array()) as $table) {
			$commands[] = $command . ' ' . $table;
		}
		
		return $commands;
	}

?>