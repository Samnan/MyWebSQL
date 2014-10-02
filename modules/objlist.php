<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/objlist.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		include(BASE_PATH . "/lib/html.php");
		include(BASE_PATH . "/lib/interface.php");
		echo '<div id="objlist">';
		echo getDatabaseTreeHTML($db);
		echo '</div>';
	}

?>