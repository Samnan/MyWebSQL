<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/tableinsert.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		$tbl = $_REQUEST["name"];

		$str = $db->getInsertStatement($tbl);
			
		if ($str === false)
			createErrorGrid($db, $db->getLastQuery());
		else {
			print "<div id='results'>".htmlspecialchars($str)."</div>";
			print "<script type=\"text/javascript\" language='javascript'> parent.transferQuery(); </script>\n";
		}
	}
?>