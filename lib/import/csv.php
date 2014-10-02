<?php
/**
 * This file is a part of MyWebSQL package
 * import functionality for CSV (Excel)
 *
 * @file:      lib/import/csv.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */


if (defined("CLASS_IMPORT_CSV_INCLUDED"))
	return true;

define("CLASS_IMPORT_CSV_INCLUDED", "1");

class Import_csv { // extends DataImport {
	var $db;
	var $options;
	var $field_names;
	var $errMsg;
	var $csv;

	var $numQueriesFailed;
	var $numRowsAffected;
	var $executionTime;
	var $lastQuery;

	static $_options = array(
		'table'         => '',
		'ignore_errors' => FALSE,
		'header'        => FALSE,
		'delimiter'     => ',',
		'enclosed'      => '"',
		'escape'        => '\\',
		//@TODO:'encoding'      => ''
	);

	// return options supported by this driver for the import
	public static function options() {
		return (self::$_options);
	}

	function __construct(&$db) {
		$this->db = $db;

		$this->numQueriesFailed = 0;
		$this->numRowsAffected = 0;
		$this->executionTime = 0;
		$this->lastQuery = '';
	}

	function error($msg) {
		$this->errMsg = $msg;
		return false;
	}

	function getError() {
		return $this->errMsg;
	}

	function getFailedQueries() {
		return $this->numQueriesFailed;
	}

	function getRowsAffected() {
		return $this->numRowsAffected;
	}

	function getExecutedTime() {
		// return formatted time output
		return $this->db->getQueryTime($this->executionTime);
	}

	function getLastQuery() {
		return $this->lastQuery;
	}

	function importTable($file, $options) {
		$this->options = $options;

		$fp = null;
		if ( !($fp = fopen($file, 'rt')) )
			return false;

		// skip first row if it contains field names
		if ($this->options['header'])
			$this->field_names = @fgetcsv( $fp, 0, $this->options['delimiter'], $this->options['enclosed'], $this->options['escape'] );

		$this->executionTime = $this->db->getMicroTime();

		while( ($data = @fgetcsv( $fp, 0, $this->options['delimiter'], $this->options['enclosed'], $this->options['escape'] )) !== false) {
			if ( !$this->addRow($data) && $this->options['ignore_errors' ] == false ) {
				$this->executionTime = $this->db->getMicroTime() - $this->executionTime;
				fclose($fp);
				return false;
			}
		}

		$this->executionTime = $this->db->getMicroTime() - $this->executionTime;
		fclose($fp);
		return TRUE;
	}

	function addRow($data) {
		if (!is_array($data))
			return false;

		$sql = 'insert into ' . $this->db->getBackQuotes() . $this->options['table'] . $this->db->getBackQuotes();

		if (is_array($this->field_names)) {
			$sql .= ' (';
			foreach($this->field_names as $field)
				$sql .= $this->db->getBackQuotes() . $field . $this->db->getBackQuotes() . ',';
			$sql = substr($sql, 0, strlen($sql) - 1);
			$sql .= ')';
		}

		$sql .= ' values (';

		foreach($data as $value)
			$sql .= "'" . $this->db->escape($value) . "',";

		$sql = substr($sql, 0, strlen($sql) - 1);

		$sql .= ')';

		if ($this->db->query($sql)) {
			$this->numRowsAffected++;
			return true;
		}

		$this->numQueriesFailed++;
		$this->lastQuery = $sql;
		return $this->error($this->db->getError());
	}
}





?>