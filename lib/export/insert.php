<?php
/**
 * This file is a part of MyWebSQL package
 * export functionality for SQL (insert commands)
 *
 * @file:      lib/export/insert.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (defined("CLASS_EXPORT_SQLINSERT_INCLUDED"))
	return true;

define("CLASS_EXPORT_SQLINSERT_INCLUDED", "1");

class Export_insert {
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
		$autof = $this->options['auto_field'];
		$table = $this->options['table'];
		$fieldNames = $this->options['fieldnames'];
		
		$bq = $this->db->getBackQuotes();
		$quotes = $this->db->getQuotes();
		
		$x = count($row);
		$res = "insert into $bq".$table.$bq;
		if ($fieldNames) {
			$res .= " (";
			for($i=0; $i<count($field_info)-1; $i++)
				$res .= $bq.$field_info[$i]->name."$bq,";
			$res .= $bq.$field_info[$i]->name."$bq)";
		}
		$res .= " values (";
		for($i=0; $i<$x; $i++) {
			if ($autof == $i)
				$res .= "NULL";
			else if ($row[$i] === NULL)
				$res .= "NULL";
			// timestamp, enum etc also show as numeric, so we need to double check
			else if ($field_info[$i]->numeric == 1 && $field_info[$i]->type == 'numeric')
				$res .= $row[$i];
			else
				$res .= $quotes . $this->db->escape($row[$i]) . $quotes;

			if ($i+1 == $x)
				$res .= ");\r\n";
			else
				$res .= ",";
		}

		return $res;
	}
}
?>