<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/exportres.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        https://github.com/Samnan/MyWebSQL
 * @license    https://github.com/Samnan/MyWebSQL/license
 */

	function processRequest(&$db) {
		$replace = array();
		echo view('exportres', $replace);
	}

?>