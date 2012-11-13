<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/download.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */
 
	function handleDownload(&$db) {
		if( !ini_get('safe_mode') ) { 	
			set_time_limit(0);
		}
		
		Session::close();
		
		if ($_REQUEST["id"] == "exportres")
			downloadResults($db);
		if ($_REQUEST["id"] == "exporttbl")
			downloadTable($db, $_REQUEST["name"]);
		if ($_REQUEST["id"] == "export")
			downloadDatabase($db);
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
						'separator' => v($_REQUEST['separator'], "\t")
					);

		$options['auto_field'] = -1;
		//if (substr($_SESSION["query"], 0, 6) == "select" && $_REQUEST["auto_null"] == "on" && Session::get('select', 'unique_table') != "")
		//	$options['auto_field'] = getAutoIncField($db, Session::get('select', 'unique_table'));

		$table = (Session::get('select', 'unique_table') != "") ? Session::get('select', 'unique_table') . "-results" : "results";
		$options['table'] = (Session::get('select', 'unique_table') != "") ? Session::get('select', 'unique_table') : '<<table>>';

		$exporter = new DataExport($db, $type);
		$exporter->sendDownloadHeader($table);
		$exporter->exportTable(Session::get('select', 'query'), $options);
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
						'separator' => v($_REQUEST['separator'], "\t")
					);

		$options['auto_field'] = -1; //($_REQUEST["auto_null"] == "on") ? getAutoIncField($db, $table) : -1;

		$sql = "select * from ". $db->quote($table);
		$exporter = new DataExport($db, $type);
		$exporter->sendDownloadHeader($table);
		$exporter->exportTable($sql, $options);
	}


	function downloadDatabase(&$db) {
		// don't make POST as REQUEST here. it won't work :P
		if ( !( is_array(v($_POST["tables"])) || is_array(v($_POST["views"])) || is_array(v($_POST["procs"]))
			  ||is_array(v($_POST["funcs"])) || is_array(v($_POST["triggers"])) ||is_array(v($_POST["events"])) ) )
			return false;

		include(BASE_PATH . '/lib/export/export.php');
		$exporter = new DataExport($db, 'insert');

		$exporter->sendDownloadHeader(Session::get('db', 'name'));
		
		echo $db->addExportHeader( Session::get('db', 'name') );
		
		$export_type = v($_REQUEST["exptype"]);
		if (is_array($_POST["tables"]) && count($_POST["tables"]) > 0)	{
			$tables = $db->getTables();

			$options = array(
				'type' => 'insert',
				'fieldnames' => v($_REQUEST['fieldnames']) == 'on' ? TRUE : FALSE
			);
			foreach($tables as $table_name) {
				// is this table required in export?
				$key = array_search($table_name, $_POST["tables"]);
				if ($key === FALSE)
					continue;

				// -- -drop command --
				if (v($_REQUEST["dropcmd"]) == "on") {
					echo "\n" . $db->getDropCommand( $table_name ) . ";\n";
				}

				// -- -structure --
				$type = "table";
				if ($export_type == "all" || $export_type == "struct") {
					print "\n/* Table structure for $table_name */\n";
					$cmd = $db->getCreateCommand('table', $table_name);
					if (v($_REQUEST["auto_null"]) == "on")	// strip out auto_increment value from create table statement
						$create_table = stripAutoIncrement($cmd);
					else
						$create_table = $cmd;
					print $create_table . ";\n";
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
					exportObject($db, $name, $_POST[$type], $db->$func());
				}
			}
		}
		
		echo $db->addExportFooter();
	}

	// =====================================

	function exportObject(&$db, $name, $list, $tables) {
		foreach($tables as $table_name) {
			$key = array_search($table_name, $list);
			if ($key === FALSE)
				continue;

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
?>