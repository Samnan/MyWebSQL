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

	// this will be used during generation of statements for bulk inserts
	var $bulk_mode;
	var $bulk_size;

	function __construct(&$db, $options) {
		$this->db = $db;
		$this->options = $options;
		$this->bulk_mode = false;
		$this->bulk_size = 0;
	}

	function createHeader($field_info) {
		return '';
	}

	function createFooter($field_info) {
		return $this->options['bulkinsert'] ? ";\r\n" : '';
	}

	function createLine($row, $field_info) {
		$autof = $this->options['auto_field'];
		$table = $this->options['table'];
		$fieldNames = $this->options['fieldnames'];

		$bq = $this->db->getBackQuotes();
		$quotes = $this->db->getQuotes();
		$res = '';

		if ( !$this->options['bulkinsert'] || !$this->bulk_mode ) {
			$res = "INSERT INTO $bq".$table.$bq;
			if ($fieldNames) {
				$res .= " (";
				for($i=0; $i<count($field_info)-1; $i++)
					$res .= $bq.$field_info[$i]->name."$bq,";
				$res .= $bq.$field_info[$i]->name."$bq)";
			}
			$res .= " VALUES (";

			// set bulk mode on so that subsequent statements do not include the INSERT INTO ... clause
			if ($this->options['bulkinsert']) {
				$this->bulk_mode = true;
				// intentionally start with extra size so that we can keep the size limited to what is desired
				$this->bulk_size = strlen($res) * 2;
			}
		} else {
			$res .= ",\r\n(";
		}

		$x = count($row);
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
				$res .= $this->options['bulkinsert'] ? ")" : ");\r\n";
			else
				$res .= ",";
		}

		// check for bulk insert mode and adjust as necessary
		if ($this->options['bulkinsert'] && $this->options['bulksize'] > 0) {
			$this->bulk_size += strlen($res);
			if ( $this->bulk_size >= $this->options['bulksize'] ) {
				$this->bulk_mode = false;
				$res .= ";\r\n";
			}
		}
		return $res;
	}
}
?>