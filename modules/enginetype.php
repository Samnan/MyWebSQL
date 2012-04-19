<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/enginetype.php
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
		$editor->loadTable(false, false, true);
		
		$message = '';
		if ($action == "alter") {
			$result = alterTableEngine($db, v($_REQUEST["enginetype"]), $editor);
			$formatted_query = preg_replace("/[\\n|\\r]?[\\n]+/", "<br>", htmlspecialchars($editor->getSql()));
			if ($result) {
				$message = 
					'<div id="message">'
					.'<div class="success">'.__('The command executed successfully').'.</div>'
					.'<div class="sql_text">'.$formatted_query.'</div>'
					.'</div>';
			} else {
				$message = 
					'<div id="message">'
					.'<div class="warning">'.__('Error occurred while executing the query').':</div>'
					.'<div class="sql_error">'.$formatted_query.'</div><div class="message">'.htmlspecialchars($db->getError()).'</div>'
					.'</div>';
			}
		}
		
		$props = $editor->getProperties();
		include('lib/html.php');
		$engines = html::arrayToOptions($db->getEngines(), $props->engine, true);
		$replace = array(
			'TABLE_NAME' => htmlspecialchars($editor->getName()),
			'ENGINE' => $engines,
			'MESSAGE' => $message,
		);
		echo view('enginetype', $replace);
	}
	
	function alterTableEngine(&$db, $engineType, &$editor) {
		$props = $editor->getProperties();
		$props->engine = $engineType;
		$editor->setProperties($props);
		
		$sql = $editor->getAlterStatement();
		
		if (!$db->query($sql))
			return false;
	
		return true;
	}
?>