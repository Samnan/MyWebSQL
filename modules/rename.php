<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/rename.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		$type = v($_REQUEST["id"]);
		$name = v($_REQUEST["name"]);
		$new_name = v($_REQUEST["query"]);

		if (!$name || !$new_name) {
			createErrorGrid($db, '');
			return;
		}

		$success = $db->renameObject($name, $type, $new_name);
		// @@TODO: this can be improved, although it is only information
		$numQueries = $type == 'table' ? 1 : 2;

		if ($success) {
			Session::set('db', 'altered', true);
			createInfoGrid($db, '', $numQueries);
		}
		else
			createErrorGrid($db, $db->getLastQuery());
	}
?>