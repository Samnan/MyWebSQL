<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/infovars.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		displayVariableList($db);
	}
	
	function displayVariableList(&$db) {
		if ($db->query("show variables")) {
			createSimpleGrid($db, __('Server Variables'));		}
	}
	
?>