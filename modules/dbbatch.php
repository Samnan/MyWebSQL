<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/dbbatch.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		if (v($_REQUEST["id"]) == 'batch') {
			$stats = array();
			$commands = array();

			if ( v($_POST['skip_fkey']) == "on" )
				$db->set_constraint( 'fkey', false );

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
			$object_list = $db->getObjectList();

			$replace = array();

			$folder = $db->name();

			echo view( array($folder.'/dbbatch', 'dbbatch'), $replace, $object_list);
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
			if ($db->dropObject($table, 'view'))
				$status['success']++;
			else
				$status['errors']++;
		}
		foreach(v($_POST['procedures'], array()) as $table) {
			if ($db->dropObject($table, 'procedure'))
				$status['success']++;
			else
				$status['errors']++;
		}
		foreach(v($_POST['functions'], array()) as $table) {
			if ($db->dropObject($table, 'function'))
				$status['success']++;
			else
				$status['errors']++;
		}
		foreach(v($_POST['triggers'], array()) as $table) {
			if ($db->dropObject($table, 'trigger'))
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
			$commands[] = $command . ' ' . $db->quote($table);
		}
		foreach(v($_POST['views'], array()) as $table) {
			$commands[] = $command . ' ' . $db->quote($table);
		}
		foreach(v($_POST['procedures'], array()) as $table) {
			$commands[] = $command . ' ' . $db->quote($table);
		}
		foreach(v($_POST['functions'], array()) as $table) {
			$commands[] = $command . ' ' . $db->quote($table);
		}
		foreach(v($_POST['triggers'], array()) as $table) {
			$commands[] = $command . ' ' . $db->quote($table);
		}

		return $commands;
	}

?>