<?php
/**
 * This file is a part of MyWebSQL package
 * Provides a generic wrapper for data import for table(s)
 *
 * @file:      lib/import/import.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (defined("CLASS_IMPORT_INCLUDED"))
	return true;

define("CLASS_IMPORT_INCLUDED", "1");

class DataImport {
	static $types = array('csv', 'txt'); 

	public static function types() {
		return (self::$types);
	}

	function factory(&$db, $type) {
		$class = 'Import_' . strtolower($type);
		require_once( dirname(__FILE__) . '/' . strtolower($type) . '.php');
		
		return new $class($db);
	}
}
?>