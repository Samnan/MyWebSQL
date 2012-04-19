<?php
/**
 * This file is a part of MyWebSQL package
 * export functionality for plain text
 *
 * @file:      lib/export/text.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (defined("CLASS_EXPORT_TEXT_INCLUDED"))
	return true;

define("CLASS_EXPORT_TEXT_INCLUDED", "1");

class Export_text {
	var $db;
	var $options;
	
	function __construct(&$db, $options) {
		$this->db = $db;
		$this->options = $options;
	}
	
	function createHeader($field_info) {
		return '';
	}
	
	function createFooter($field_info) {
		return '';
	}
	
	function createLine($row, $field_info) {
		$separator = ($this->options['separator'] == '\t') ? "\t" : $this->options['separator'];
		$fieldwrap = "\"";
		$res = '';
		$x = count($row);
		for($i=0; $i<$x; $i++) {
			if ($row[$i] === NULL)
				$res .= "NULL";
			else if ($field_info[$i]->numeric == 1)
				$res .= $row[$i];
			else
				$res .= $fieldwrap .  $row[$i] . $fieldwrap;

			if ($i+1 == $x)
				$res .= "\r\n";
			else
				$res .= $separator;
		}
		return $res;
	}
}
?>