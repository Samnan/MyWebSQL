<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/infodb.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		if (getDbName() == '') {
			echo view('invalid_request');
			return;
		}

		if ($db->queryTableStatus())
			createSimpleGrid($db, __('Database summary').': ['.htmlspecialchars(getDbName()).']');
	}

?>