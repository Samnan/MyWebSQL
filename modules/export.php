<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/export.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        https://github.com/Samnan/MyWebSQL
 * @license    https://github.com/Samnan/MyWebSQL/license
 */

	function processRequest(&$db) {
		$object_list = $db->getObjectList();

		$replace = array();

		$folder = $db->name();

		echo view( array($folder.'/export', 'export'), $replace, $object_list);
	}

?>