<?php
/**
 * This file is a part of MyWebSQL package
 * export functionality for XML
 *
 * @file:      lib/export/xml.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (defined("CLASS_EXPORT_XML_INCLUDED"))
	return true;

define("CLASS_EXPORT_XML_INCLUDED", "1");

class Export_xml {
	var $db;
	var $options;
	
	function __construct(&$db, $options) {
		$this->db = $db;
		$this->options = $options;
	}
	
	function createHeader($field_info) {
		return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<data>\n";
	}
	
	function createFooter($field_info) {
		return "</data>";
	}
	
	function createLine($row, $field_info) {
		$x = count($row);
		$res = "<row>\n";
		for($i=0; $i<$x; $i++) {
			$res .= "\t<" . $field_info[$i]->name . ">";
			if ($row[$i] === NULL)
				$res .= "NULL";
			else if ($field_info[$i]->numeric == 1)
				$res .= $row[$i];
			else
				$res .= "<![CDATA[" .  $row[$i] . "]]>";

			$res .= "</" . $field_info[$i]->name . ">\n";
		}
		$res .= "</row>\n";
		return $res;
	}
}
?>