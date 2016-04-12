<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/describe.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		if ( getDbName() == '' || !isset($_REQUEST['name']) ) {
			echo view('invalid_request');
			return;
		}

		if ( $db->getTableDescription( $_REQUEST['name'] ) )
			createSimpleGrid($db, __('Table Description').': ['.htmlspecialchars($_REQUEST['name']).']');
	}

?>