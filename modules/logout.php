<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/logout.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		Session::destroy();
		echo view('logout');
	}

?>