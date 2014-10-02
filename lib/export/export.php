<?php
/**
 * This file is a part of MyWebSQL package
 * Provides a generic wrapper for data export modules
 *
 * @file:      lib/export/export.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (defined("CLASS_EXPORT_INCLUDED"))
	return true;

define("CLASS_EXPORT_INCLUDED", "1");

class DataExport {
	var $db;
	var $type;
	var $driver;
	var $errMsg;

	static $types = array('insert', 'xml', 'xhtml', 'csv', 'text', 'yaml');

	public static function types() {
		return (self::$types);
	}

	function __construct(&$db, $type) {
		$this->db = $db;
		$this->type = $type;
	}

	function sendDownloadHeader($name) {
		$type = '.sql';
		if ($this->type == 'xml') $type = '.xml';
		else if ($this->type == 'xhtml') $type = '.html';
		else if ($this->type == 'csv') $type = '.csv';
		else if ($this->type == 'text') $type = '.txt';
		else if ($this->type == 'yaml') $type = '.yml';
		header("Content-disposition: attachment;filename=".$name.$type);
	}

	// common download function for all types of exports
	function exportTable($sql, $options) {
		// check if we need to apply limit to the result set or not
		$applyLimit = isset($options['apply_limit']) ? $options['apply_limit'] :
			( ! ( strpos($sql, "limit ") || ("select" != strtolower(substr($sql, 0, 6))) ) );

		$class = 'Export_' . strtolower($this->type);
		require_once( dirname(__FILE__) . '/' . strtolower($this->type) . '.php');
		$this->driver = new $class($this->db, $options);

		$id = 0;
		$field_info = NULL;

		while(1) {
			$tempSql = $sql;
			if ($applyLimit)
				$tempSql .= " " . $this->db->getLimit(100, $id);

			if (!$this->db->query($tempSql, "_temp"))
				return false;

			if ($field_info == NULL) {
				$field_info = $this->db->getFieldInfo("_temp");
				print $this->driver->createHeader($field_info);
			}

			$numRows = $this->db->numRows("_temp");

			while($row = $this->db->fetchRow("_temp", 'num')) {
				print $this->driver->createLine($row, $field_info);
			}

			if ($numRows == 0 || !$applyLimit)
				break;

			$id += 100;
		}

		print $this->driver->createFooter($field_info);
	}

	function error($msg) {
		$this->errMsg = $msg;
		return false;
	}

	function getError() {
		return $this->errMsg;
	}
}
?>