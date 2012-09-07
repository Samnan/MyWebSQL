<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/altertbl.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */
	function processRequest(&$db) {
		$action = v($_REQUEST["id"]);
		include(BASE_PATH . "/lib/tableeditor.php");
		$editor = new tableEditor($db);
		if ($action == "alter") {
			$result = alterDatabaseTable($db, v($_REQUEST["query"]), $editor);
			$formatted_query = preg_replace("/[\\n|\\r]?[\\n]+/", "<br>", htmlspecialchars($editor->getSql()));
			if ($result) {
				print
					'<div id="result">1</div><div id="message">'
					.'<div class="message ui-state-default">'.__('The command executed successfully').'.</div>'
					.'<div class="sql-text ui-state-default">'.$formatted_query.'</div>'
					.'</div>';
			} else {
				print
					'<div id="result">0</div><div id="message">'
					.'<div class="message ui-state-error">'.__('Error occurred while executing the query').':</div>'
					.'<div class="message ui-state-highlight">'.htmlspecialchars($db->getError()).'</div>'
					.'<div class="sql-text ui-state-error">'.$formatted_query.'</div>'
					.'</div>';
			}
		} else {
			$editor->setName(v($_REQUEST["name"]));
			$editor->loadTable();
			displayTableEditorForm($db, $editor);
		}
	}
	
	function displayTableEditorForm(&$db, &$editor) {
		$rows = $editor->getFields();

		$props = $editor->getProperties();
		$sel_engine = $props->engine;
		$sel_charset = $props->charset;
		$sel_collation = $props->collation;
		$comment = $props->comment;
			
		include(BASE_PATH . '/lib/html.php');
		$engines = html::arrayToOptions($db->getEngines(), $sel_engine, false);
		$charsets = html::arrayToOptions($db->getCharsets(), $sel_charset, false);
		$collations = html::arrayToOptions($db->getCollations(), $sel_collation, false);
	
		$replace = array(
						'ID' => v($_REQUEST["id"]) ? htmlspecialchars($_REQUEST["id"]) : '',
						'MESSAGE' => '',
						'ROWINFO' => json_encode($rows),
						'ALTER_TABLE' => 'true',
						'TABLE_NAME' => htmlspecialchars($editor->getName()),
						'ENGINE' => $engines,
						'CHARSET' => $charsets,
						'COLLATION' => $collations,
						'COMMENT' => htmlspecialchars($comment)
						);
		echo view('editable', $replace);
	}
	
	function alterDatabaseTable(&$db, $info, &$editor) {
		$info = json_decode($info);
		
		if (!is_object($info))
			return false;
		
		if (v($info->name))
			$editor->setName($info->name);
		if (v($info->delfields))
			$editor->deleteFields($info->delfields);
		if (v($info->fields))
			$editor->setFields($info->fields);
		if (v($info->props))
			$editor->setProperties($info->props);
		
		$sql = $editor->getAlterStatement();
		
		if (!$db->query($sql))
			return false;
	
		return true;
	}
?>