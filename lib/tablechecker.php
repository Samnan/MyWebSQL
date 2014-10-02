<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      lib/tablechecker.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

class tableChecker {
	var $db;
	var $tables;
	var $operation;
	var $options;

	var $_sql; // last generated sql is saved for debugging purposes

	function __construct(&$db) {
		$this->db = $db;
	}

	function setTables($tables) {
		$this->tables = $tables;
	}

	function setOperation($op) {
		$this->operation = $op;
	}

	function setOptions($options) {
		$this->options = $options;
	}

	function getSql() {
		return $this->_sql;
	}

	function runCheck() {
		$cmd = $this->operation;

		if (v($this->options['skiplog']) === TRUE)
			$cmd .= ' NO_WRITE_TO_BINLOG';

		$cmd .= ' tables ';

		$bq = $this->db->getBackQuotes();

		foreach($this->tables as $table)
			$cmd .= $bq . $this->db->escape($table) . $bq . ',';

		$cmd = substr($cmd, 0, -1);

		$optfunc = $this->operation . 'Options';
		if (method_exists($this, $optfunc))
			$cmd .= ' ' . $this->$optfunc();

		$this->_sql = $cmd;

		if (!$this->db->query($this->_sql))
			return false;

		return true;
	}

	function checkOptions() {
		$str = '';

		switch($this->options['checktype']) {
			case 'quick': $str .= ' QUICK';
				break;
			case 'extended': $str .= ' EXTENDED';
				break;
			case 'fast': $str .= ' FAST';
				break;
			case 'meduin': $str .= ' MEDIUM';
				break;
			case 'changed': $str .= ' CHANGED';
				break;
		}

		return $str;
	}

	function repairOptions() {
		$str = '';

		if (in_array('quick', $this->options['repairtype']))
			$str .= ' QUICK';
		else if (in_array('extended', $this->options['repairtype']))
			$str .= ' EXTENDED';
		else if (in_array('usefrm', $this->options['repairtype']))
			$str .= ' USE_FRM';

		return $str;
	}

	function getResults() {
		$results = array();
		while($row = $this->db->fetchRow()) {
			switch($row['Msg_type']) {
				case 'Error':
					$results[$row['Table']] = array('type' => 'error', 'msg' => $row['Msg_text']);
					break;
				case 'note':
					if(!isset($results[$row['Table']]))
						$results[$row['Table']] = array('type' => 'note', 'msg' => $row['Msg_text']);
					break;
				case 'status':
					if(!isset($results[$row['Table']]))
						$results[$row['Table']] = array('type' => 'success', 'msg' => $row['Msg_text']);
					break;
			}
		}

		return $results;
	}
}
?>