<?php
/**
 * This file is a part of MyWebSQL package
 * A simple and easy to debug sqlite wrapper class
 *
 * @file:      lib/db/sqlite3.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (defined("CLASS_DB_SQLITE3_INCLUDED"))
	return true;

define("CLASS_DB_SQLITE3_INCLUDED", "1");

require_once(BASE_PATH . '/lib/db/sqlite.php');
class DB_Sqlite3 extends DB_Sqlite {

	protected $stack_last;

	function connect($ip, $user, $password, $db="") {
		if (substr($ip, -1) != '/')
			$ip .= '/';
		// must be a directory and writable
		if (!is_dir($ip) || !is_writable($ip))
			return $this->error(__('SQLite database folder is inaccessible or not writable'));

		// this helps authenticate first time with user defined login information
		if (isset($this->authOptions['user']) && $user != $this->authOptions['user'])
			return $this->error(__('Invalid Credentials'));

		if (isset($this->authOptions['password']) && $password != $this->authOptions['password'])
			return $this->error(__('Invalid Credentials'));

		if (!class_exists('SQLite3')) {
			return $this->error(str_replace('{{NAME}}', 'SQLite3', __('{{NAME}} client library is not installed')));
		}

		if ($db) {
			try {
				$this->conn = new SQLite3($ip . $db);
			} catch(Exception $e) {
				return $this->error('Access denied or failed to open database');
			}
		}

		$this->ip = $ip;
		$this->user = $user;
		$this->password = $password;
		$this->db = $db;

		$this->selectVersion();

		//$this->query("SET CHARACTER SET 'utf8'");
		//$this->query("SET collation_connection = 'utf8_general_ci'");

		return true;
	}

	function disconnect() {
		if ($this->conn) {
			$this->conn->close();
		}
		$this->conn = false;
		return true;
	}

	function selectDb($db) {
		$this->db = $db;
		try {
			$this->conn = new SQLite3($this->ip . $db);
		} catch(Exception $e) {
			return false;
		}

		$this->selectVersion();
		return true;
	}

	function query($sql, $stack=0) {		// call with query($sql, 1) to store multiple results
		if (!$this->conn) {
			log_message("DB: Connection has been closed");
			return false;
		}

		$this->result[$stack] = "";
		$this->stack_last = $stack;

		$this->lastQuery = $sql;
		$this->queryTime = $this->getMicroTime();
		$this->result[$stack] = $this->conn->query($sql);
		$this->queryTime = $this->getMicroTime() - $this->queryTime;

		if (!$this->result[$stack]) {
			$this->errMsg = $this->conn->lastErrorMsg();
			log_message("DB: $sql ::: ".$this->errMsg);
			return false;
		}

		return true;
	}

	function getInsertID() {
		return $this->conn->lastInsertRowID();
	}

	function fetchRow($stack=0, $type="") {
		if($type == "")
			$type = SQLITE3_BOTH;
		else if ($type == "num")
			$type = SQLITE3_NUM;
		else if ($type == "assoc")
			$type = SQLITE3_ASSOC;

		if (!is_object($this->result[$stack])) {
			log_message("DB: called fetchRow[$stack] but result is invalid");
			return false;
		}
		return $this->result[$stack]->fetchArray( $type );
	}

	function fetchSpecificRow($num, $type="", $stack=0) {
		// @@TODO: find a workaround to fetch specific row from sqlite3 result object
		return false;
	}

	function numRows($stack=0) {
		if ($this->result[$stack] !== TRUE && $this->result[$stack] !== FALSE) {
			$sql = 'SELECT COUNT(*) FROM (' . $this->lastQuery . ')';
			if ( $this->query($sql, '_numrows') ) {
				$result = $this->fetchRow('_numrows');
				return $result[0];
			}
		}
		return 0;
	}

	function escape($str) {
		return $this->conn->escapeString($str);
	}

	function getAffectedRows() {
		return $this->result[$this->stack_last]->changes();
	}

	/**************************************/
	function getFieldInfo($stack=0) {
		$fields = array();
		$i = 0;
		while ($i < $this->result[$stack]->numColumns()) {
			$f = new StdClass;
			$f->name = $this->result[$stack]->columnName($i);
			$f->table = '';
			$f->not_null = 0;
			$f->blob = 0;
			$f->pkey = 0;
			$f->ukey = 0;
			$f->mkey = 0;
			$f->zerofill = 0;
			$f->unsigned = 0;
			$f->autoinc = 0;
			$f->numeric = 0;

			$f->type = 'string';
			$fields[] = $f;
			$i++;
		}
		return $fields;
	}

	function selectVersion() {
		// unless a database is opened, we need a fallback version info
		if ($this->conn) {
			$version = $this->conn->version();
		} else {
			$version = array('versionString' => '0');
		}
		Session::set('db', 'version', intval($version['versionString']));
		Session::set('db', 'version_full', $version['versionString']);
		Session::set('db', 'version_comment', '');
	}

}
?>