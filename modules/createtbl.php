<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/createtbl.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */
	function processRequest(&$db) {
		$action = v($_REQUEST["id"]);
		if ($action == "create" || $action == "alter") {
			include(BASE_PATH . "/lib/tableeditor.php");
			$editor = new tableEditor($db);
			$result = createDatabaseTable($db, v($_REQUEST["query"]), $editor);
			$formatted_query = preg_replace("/[\\n|\\r]?[\\n]+/", "<br>", htmlspecialchars($editor->getSql()));
			if ($result)
				print
					'<div id="result">1</div><div id="message">'
					.'<div class="message ui-state-default">'.__('The command executed successfully').'.</div>'
					.'<div class="sql-text ui-state-default">'.$formatted_query.'</div>'
					.'</div>';
			else				
				print
					'<div id="result">0</div><div id="message">'
					.'<div class="message ui-state-error">'.__('Error occurred while executing the query').':</div>'
					.'<div class="sql-text ui-state-error">'.$formatted_query.'</div>'
					.'<div class="message ui-state-highlight">'.htmlspecialchars($db->getError()).'</div>'
					.'</div>';
		}
		else
			displayCreateTableForm($db);
	}
	
	function displayCreateTableForm(&$db) {
		$rows = array();

		include(BASE_PATH . '/lib/html.php');
		$engines = html::arrayToOptions($db->getEngines(), '', true);
		$charsets = html::arrayToOptions($db->getCharsets(), '', true);
		$collations = html::arrayToOptions($db->getCollations(), '', true);
		$comment = '';
	
		$replace = array(
						'ID' => v($_REQUEST["id"]) ? htmlspecialchars($_REQUEST["id"]) : '',
						'MESSAGE' => '',
						'ROWINFO' => json_encode($rows),
						'ALTER_TABLE' => 'false',
						'TABLE_NAME' => '',
						'ENGINE' => $engines,
						'CHARSET' => $charsets,
						'COLLATION' => $collations,
						'COMMENT' => htmlspecialchars($comment)
						);
		echo view('editable', $replace);
	}
	
	function createDatabaseTable(&$db, $info, &$editor) {
		$info = json_decode($info);
		
		if (!is_object($info))
			return false;
		
		if (v($info->name))
			$editor->setName($info->name);
		if (v($info->fields))
			$editor->setFields($info->fields);
		if (v($info->props))
			$editor->setProperties($info->props);
		
		$sql = $editor->getCreateStatement();
		
		if (!$db->query($sql))
			return false;
	
		return true;
	}
?>