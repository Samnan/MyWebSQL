<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/indexes.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		$action = v($_REQUEST["id"]);
		include("lib/tableeditor.php");
		$editor = new tableEditor($db);
		$editor->setName(v($_REQUEST["name"]));
		$editor->loadTable(true, true, false);

		if ($action == "alter")
		{
			$result = alterTableIndexes($db, v($_REQUEST["query"]), $editor);
			$formatted_query = preg_replace("/[\\n|\\r]?[\\n]+/", "<br>", htmlspecialchars($editor->getSql()));
			if ($result)
				print
					'<div id="result">1</div><div id="message">'
					.'<div class="success">The command executed successfully.</div>'
					.'<div class="sql_text">'.$formatted_query.'</div>'
					.'</div>';
			else				
				print
					'<div id="result">0</div><div id="message">'
					.'<div class="warning">Error occured while executing the query:</div>'
					.'<div class="sql_error">'.$formatted_query.'</div><div class="message">'.htmlspecialchars($db->getError()).'</div>'
					.'</div>';
		}
		else
			displayIndexesForm($db, $editor);
	}
	
	function displayIndexesForm(&$db, &$editor)
	{
		$indexes = $editor->getIndexes();
		$fields = $editor->getFields();

		$replace = array(
						'ID' => v($_REQUEST["id"]) ? htmlspecialchars($_REQUEST["id"]) : '',
						'MESSAGE' => __('Changes are not saved until you press [Save All Changes]'),
						'INDEXES' => count($indexes) > 0 ? json_encode($indexes) : '{}',
						'FIELDS' => json_encode($fields),
						'TABLE_NAME' => htmlspecialchars($editor->getName())
						);
		echo view('indexes', $replace);
	}
	
	function alterTableIndexes(&$db, $info, &$editor)
	{
		$info = json_decode($info);
		
		if (!is_object($info))
			return false;
		
		if (v($info->indexes))
			$editor->setIndexes($info->indexes);
		
		$sql = $editor->getAlterIndexStatement();
		
		if (!$db->query($sql))
			return false;
	
		return true;
	}
?>