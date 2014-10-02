<?php
/**
 * This file is a part of MyWebSQL package
 * export functionality for XHTML
 *
 * @file:      lib/export/xhtml.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (defined("CLASS_EXPORT_XHTML_INCLUDED"))
	return true;

define("CLASS_EXPORT_XHTML_INCLUDED", "1");

class Export_xhtml {
	var $db;
	var $options;

	function __construct(&$db, $options) {
		$this->db = $db;
		$this->options = $options;
	}

	function createHeader($field_info) {
		$header = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
				. "<head>\n"
				. "<META NAME=\"Author\" CONTENT=\"MyWebSQL\">"
				. "<title>Export Data</title>\n"
				. "<style>\n"
				. "div { float:left;padding:5px }\n"
				. "div.field { background-color:#efefef;width:100px }\n"
				. "div.data { clear:both }\n"
				. "div.row { clear:both }\n"
				. "div.row div { width:100px;overflow:hidden }\n"
				. "</style>\n"
				. "</head>\n<body>\n\t<div class=\"header\">\n\t\t";
			for($i=0; $i<count($field_info); $i++)
				$header .= "<div class=\"field\">" . htmlspecialchars($field_info[$i]->name) . "</div>";
			$header .= "\n\t</div>\n\t<div class=\"data\">\n";
			return $header;
	}

	function createFooter($field_info) {
		return "</div>\n</body>\n</html>";
	}

	function createLine($row, $field_info) {
		$x = count($row);
		$res = "\t\t<div class=\"row\">\n\t\t\t";
		for($i=0; $i<$x; $i++) {
			$res .= "<div>";
			if ($row[$i] === NULL)
				$res .= "NULL";
			else if ($field_info[$i]->numeric == 1)
				$res .= $row[$i];
			else
				$res .= htmlspecialchars($row[$i]);

			$res .= "</div>";
		}
		$res .= "\n\t\t</div>\n";

		return $res;
	}
}
?>