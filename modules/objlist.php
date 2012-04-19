<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/objlist.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		include("lib/html.php");
		include("lib/interface.php");
		echo '<div id="objlist">';
		createDatabaseTree($db);
		echo '</div>';
	}

?>