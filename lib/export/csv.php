<?php
/**
 * This file is a part of MyWebSQL package
 * export functionality for CSV (Excel)
 *
 * @file:      lib/export/csv.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */
 

if (defined("CLASS_EXPORT_CSV_INCLUDED"))
	return true;

define("CLASS_EXPORT_CSV_INCLUDED", "1");

class Export_csv {
	var $db;
	var $options;
	
	function __construct(&$db, $options) {
		$this->db = $db;
		$this->options = $options;
	}
	
	function createHeader($field_info) {
		if ($this->options['fieldheader']) {
			$separator = ",";
			$fieldwrap = "\"";
			$x = count($field_info);
			$res = '';
			for($i=0; $i<$x; $i++) {
				$res .= $fieldwrap .  str_replace('"', '""', $field_info[$i]->name) . $fieldwrap;
				if ($i+1 == $x)
					$res .= "\r\n";
				else
					$res .= $separator;
			}
			return $res;
		}
		return '';
	}
	
	function createFooter($field_info) {
		return '';
	}
	
	function createLine($row, $field_info) {
		$separator = ",";
		$fieldwrap = "\"";
		$x = count($row);
		
		$res = '';
		for($i=0; $i<$x; $i++) {
			if ($row[$i] === NULL)
				$res .= $fieldwrap . "NULL" . $fieldwrap;
			else if ($field_info[$i]->numeric == 1)
				$res .= $row[$i];
			else
				$res .= $fieldwrap .  str_replace('"', '""', $row[$i]) . $fieldwrap;

			if ($i+1 == $x)
				$res .= "\r\n";
			else
				$res .= $separator;
		}
		return $res;
	}
}
?>