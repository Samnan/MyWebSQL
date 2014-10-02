<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/truncate.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		$type = v($_REQUEST["id"]);
		$name = v($_REQUEST["name"]);

		if (!$name) {
			createErrorGrid($db, '');
			return;
		}

		if ($db->truncateTable($name)) {
			createInfoGrid($db, $db->getLastQuery());
		}
		else
			createErrorGrid($db, $db->getLastQuery());
	}
?>