<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/infovars.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		if ($db->queryVariables()) {
			createSimpleGrid($db, __('Server Variables'));
		}
	}
	
?>