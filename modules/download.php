<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/download.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        https://github.com/Samnan/MyWebSQL
 * @license    https://github.com/Samnan/MyWebSQL/license
 */

	function processRequest(&$db) {
		if( !ini_get('safe_mode') ) {
			set_time_limit(0);
		}

		include_once(BASE_PATH . "/lib/backupstate.php");

		// the browser polls status.php while this request is still running. The token has to
		// be registered in the session before we release it, otherwise the poll cannot
		// tell whether the backup belongs to the user asking about it
		$backup_token = BackupState::sanitizeToken( v($_REQUEST['token']) );
		if ( $backup_token !== false ) {
			Session::set('backup', 'token', $backup_token);
			// the dump is written to a file, so a disconnected browser (dialog closed,
			// proxy timeout) is no reason to throw away a backup that is halfway done
			ignore_user_abort(true);
		}

		Session::close();

		switch( $_REQUEST['id'] ) {
			case 'backup': {
				include_once(BASE_PATH . "/config/backups.php");
				$compression = v($_REQUEST['compression']);
				$filename = v($_REQUEST['filename']);
				$file = get_backup_filename( $compression, $filename );

				$state = false;
				if ( $backup_token !== false ) {
					BackupState::cleanup();
					$state = new BackupState( $backup_token );
					$state->begin( countBackupObjects($db), Session::get('db', 'name'), $file ? basename($file) : '' );
					// a fatal error (memory limit, killed worker) must not leave the dialog polling forever
					register_shutdown_function( 'backupShutdown', $state );
				}

				if ( $file ) {
					include_once(BASE_PATH . "/lib/output.php");
					$output = new Output( $file, $compression );
					$message = '<div class="message ui-state-highlight">'.__('Database backup successfully created').'</div>';
					if ( $output->is_valid() ) {
						$output->progress = $state ? $state : null;
						$written = downloadDatabase($db, false, $state);
						$output->end();

						if ( $written === false ) {
							@unlink( $file );	// nothing was selected, do not leave an empty backup behind
							$message = '<div class="message ui-state-error">'.__('Select objects to include in backup').'</div>';
							if ( $state )
								$state->finish( 'error', __('Select objects to include in backup') );
						}
						else if ( $state ) {
							clearstatcache();
							$size = @filesize( $file );
							$state->finish( 'done', __('Database backup successfully created'),
								array( 'size' => $size === false ? 0 : $size ) );
						}
					} else {
						$message = '<div class="message ui-state-error">'.__('Failed to create database backup').'</div>';
						if ( $state )
							$state->finish( 'error', __('Backup folder does not exist or is not writable') );
					}
				} else {
					$message = '<div class="message ui-state-error">'.__('Invalid filename format').'</div>';
					if ( $state )
						$state->finish( 'error', __('Invalid filename format') );
				}

				if ( $state ) {
					// the dialog stays where it is and shows the result, no page reload
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode( $state->getData() );
				} else {
					echo view( 'backup', array( 'MESSAGE' => $message, 'FILENAME' => htmlspecialchars($filename) ), $db->getObjectList() );
				}
			} break;
			case 'exportres': {
				downloadResults($db);
			} break;
			case 'exporttbl': {
				downloadTable($db, $_REQUEST['name']);
			} break;
			case 'export': {
				downloadDatabase($db);
			} break;
		}
	}

	function downloadResults(&$db) {
		include(BASE_PATH . '/lib/export/export.php');
		$type = 'insert';
		$exptype = v($_REQUEST['exptype']);
		if(in_array($exptype, DataExport::types()))
			$type = $exptype;
		$options = array(
						'table' => '',
						'fieldnames' => v($_REQUEST['fieldnames']) == 'on' ? TRUE : FALSE,
						'fieldheader' => v($_REQUEST['fieldheader']) == 'on' ? TRUE : FALSE,
						'separator' => v($_REQUEST['separator'], "\t"),
						'bulkinsert' => false,
						// explicitly tell the exporter to not apply any limits
						'apply_limit' => false
					);

		$options['auto_field'] = -1;
		//if (substr($_SESSION["query"], 0, 6) == "select" && $_REQUEST["auto_null"] == "on" && Session::get('select', 'unique_table') != "")
		//	$options['auto_field'] = getAutoIncField($db, Session::get('select', 'unique_table'));

		$table = (Session::get('select', 'unique_table') != "") ? Session::get('select', 'unique_table') : '';
		$filename = $table ? $table . "-results" : "results";
		$options['table'] = $table != "" ? $table : '<<table>>';

		$exporter = new DataExport($db, $type);
		$exporter->sendDownloadHeader($filename);
		echo $db->addExportHeader( Session::get('select', 'query'), 'query', $type );
		$exporter->exportTable(Session::get('select', 'query'), $options);
		echo $db->addExportFooter( $type );
	}

	function downloadTable(&$db, $table) {
		if ($table == "")
			return false;

		include(BASE_PATH . '/lib/export/export.php');
		$type = 'insert';
		$exptype = v($_REQUEST['exptype']);
		if(in_array($exptype, DataExport::types()))
			$type = $exptype;
		$options = array(
						'table' => $table,
						'fieldnames' => v($_REQUEST['fieldnames']) == 'on' ? TRUE : FALSE,
						'fieldheader' => v($_REQUEST['fieldheader']) == 'on' ? TRUE : FALSE,
						'separator' => v($_REQUEST['separator'], "\t"),
						'bulkinsert' => v($_REQUEST['bulkinsert']),
						'bulksize' => v($_REQUEST['bulklimit']) == 'on' ? v($_REQUEST['bulksize'])*1024 : 0
					);

		$options['auto_field'] = -1; //($_REQUEST["auto_null"] == "on") ? getAutoIncField($db, $table) : -1;

		$sql = "select * from ". $db->quote($table);
		$exporter = new DataExport($db, $type);
		$exporter->sendDownloadHeader($table);
		echo $db->addExportHeader( $table, 'table', $type );
		$exporter->exportTable($sql, $options);
		echo $db->addExportFooter( $type );
	}


	// number of objects the backup is going to write, used to show a meaningful progress bar
	function countBackupObjects(&$db) {
		$total = 0;
		if ( is_array(v($_POST["tables"])) )
			$total += count($_POST["tables"]);

		$export_type = v($_REQUEST["exptype"]);
		if ($export_type == "all" || $export_type == "struct") {
			$object_types = $db->getObjectTypes();
			unset($object_types[0]);	// tables are already counted above
			foreach($object_types as $type) {
				if ( is_array(v($_POST[$type])) )
					$total += count($_POST[$type]);
			}
		}

		return $total;
	}

	// last resort reporting: if the request dies before the backup is marked as finished,
	// record why, so the dialog shows an error instead of polling forever
	function backupShutdown( $state ) {
		if ( $state->isFinished() )
			return;

		$message = __('Backup was interrupted before it could complete');
		$error = error_get_last();
		$fatal = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
		if ( is_array($error) && in_array($error['type'], $fatal) )
			$message .= ': ' . $error['message'];

		$state->finish( 'error', $message );
	}

	function downloadDatabase(&$db, $headers = true, $state = false) {
		// don't make POST as REQUEST here. it won't work :P
		if ( !( is_array(v($_POST["tables"])) || is_array(v($_POST["views"])) || is_array(v($_POST["procs"]))
			  ||is_array(v($_POST["funcs"])) || is_array(v($_POST["triggers"])) ||is_array(v($_POST["events"])) ) )
			return false;

		include(BASE_PATH . '/lib/export/export.php');
		$exporter = new DataExport($db, 'insert');

		if ( $headers )
			$exporter->sendDownloadHeader( Session::get('db', 'name') );

		echo $db->addExportHeader( Session::get('db', 'name'), 'db', 'insert' );

		$export_type = v($_REQUEST["exptype"]);
		if (is_array($_POST["tables"]) && count($_POST["tables"]) > 0)	{
			$tables = flattenTableNames( $db->getTables() );

			$options = array(
				'type' => 'insert',
				'fieldnames' => v($_REQUEST['fieldnames']) == 'on' ? TRUE : FALSE,
				'bulkinsert' => v($_REQUEST['bulkinsert']),
				'bulksize' => v($_REQUEST['bulklimit']) == 'on' ? v($_REQUEST['bulksize'])*1024 : 0
			);
			if ( $state )
				$options['progress'] = array($state, 'rows');

			foreach($tables as $table_name) {
				// is this table required in export?
				$key = array_search($table_name, $_POST["tables"]);
				if ($key === FALSE)
					continue;

				if ( $state )
					$state->step('table', $table_name);

				// -- -truncate command --
				if (v($_REQUEST["emptycmd"]) == "on") {
					echo "\n" . $db->getTruncateCommand( $table_name ) . ";\n";
				}

				// -- -drop command --
				if (v($_REQUEST["dropcmd"]) == "on") {
					echo "\n" . $db->getDropCommand( $table_name ) . ";\n";
				}

				// -- -structure --
				$type = "table";
				if ($export_type == "all" || $export_type == "struct") {
					print "\n/* Table structure for $table_name */\n";
					$cmd = $db->getCreateCommand('table', $table_name);
					// strip out auto_increment value from create table statement
					if (v($_REQUEST["auto_null"]) == "on")
						$cmd = stripAutoIncrement($cmd);
					// strip out table engine type from create table statement
					if (v($_REQUEST["exclude_type"]) == "on")
						$cmd = stripTableType($cmd);
					// strip out table charset from create table statement
					if (v($_REQUEST["exclude_charset"]) == "on")
						$cmd = stripTableCharset($cmd);
					print $cmd . ";\n";
				}

				// -- -table data --
				if ($export_type == "all" || $export_type == "data") {
					$options['auto_field'] = v($_REQUEST["auto_null"]) == "on" ? $db->getAutoIncField($table_name) : -1;
					$options['table'] = $table_name;

					$sql = "SELECT * FROM " . $db->quote($table_name);
					print "\n/* data for Table $table_name */\n";

					$exporter->exportTable($sql, $options);
				}
			}
		}

		if ($export_type == "all" || $export_type == "struct") {		// views, procedures etc do not have any data
			$object_types = $db->getObjectTypes();
			// skip tables as we have already done it
			unset($object_types[0]);
			foreach($object_types as $type) {
				if (is_array(v($_POST[$type])) && count($_POST[$type]) > 0) {
					$func = 'get' . ucfirst( $type );
					$name = substr($type, 0, -1);
					exportObject($db, $name, $_POST[$type], $db->$func(), $state);
				}
			}
		}

		echo $db->addExportFooter();
	}

	// =====================================

	function exportObject(&$db, $name, $list, $tables, $state = false) {
		foreach($tables as $table_name) {
			$key = array_search($table_name, $list);
			if ($key === FALSE)
				continue;

			if ( $state )
				$state->step($name, $table_name);

			if (v($_REQUEST["dropcmd"]) == "on")
				print "\ndrop $name if exists " . $db->quote($table_name) . ";\n";

			print "\n/* create command for $table_name */\n";
			print "\nDELIMITER $$\n";
			print $db->getCreateCommand($name, $table_name) . "$$\n";
			print "\nDELIMITER ;\n";
		}
	}

	function stripAutoIncrement($statement) {
		//return preg_replace("/AUTO_INCREMENT=[0-9]+\s/", "", $statement); // a chance of bug with a crazy field name like `AUTO_INCREMENT=1000 ` :P
		preg_match("/.*\).*(AUTO_INCREMENT=[0-9]+ )/", $statement, $matches);
		if (isset($matches[1]))
			$statement = str_replace($matches[1], "", $statement);
		return $statement;
	}

	function stripTableType($statement) {
		preg_match("/.*\).*(ENGINE=[a-zA-Z]+ )/", $statement, $matches);
		if (isset($matches[1]))
			$statement = str_replace($matches[1], "", $statement);
		return $statement;
	}

	function stripTableCharset($statement) {
		preg_match("/.*\).*(DEFAULT\sCHARSET=[a-zA-Z0-9]+)/", $statement, $matches);
		if (isset($matches[1]))
			$statement = str_replace($matches[1], "", $statement);
		return $statement;
	}

	// flattens table names by combining schema and table name together (if required)
	function flattenTableNames( $arr ) {
		foreach( $arr as $val ) {
			if ( !is_array ($val) )
				return $arr;
		}

		$ret = array();
		foreach( $arr as $schema => $tables ) {
			foreach( $tables as $table ) {
				$ret[] = $schema . '.' . $table;
			}
		}

		return $ret;
	}
?>