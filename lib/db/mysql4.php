<?php
/**
 * This file is a part of MyWebSQL package
 * A simple and easy to debug mysql4 wrapper class
 *
 * @file:      lib/db/mysql4.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (defined("CLASS_DB_MYSQL4_INCLUDED"))
	return true;

define("CLASS_DB_MYSQL4_INCLUDED", "1");

class DB_Mysql4 {
	var $ip, $user, $password, $db;
	var $conn;
	var $result;		// array
	var $errMsg;
	var $escapeData;
	var $lastQuery;
	var $queryTime;

	function DB_Mysql4() {
		$this->conn = null;
		$this->errMsg = null;
		$this->escapeData = true;
		$this->result = array();
	}

	function name() {
		return 'mysql';
	}

	function hasServer() {
		return true;
	}
	
	function hasObject($type) {
		switch($type) {
			case 'table':
				return true;
				break;
		}
		return false;
	}
	
	function getObjectTypes() {
		$types = array(
			'tables'
		);
	
		return $types;
	}
	
	function getObjectList() {
		$data = array(
			'tables' => $this->getTables()
		);
		
		return $data;
	}
	
	function getBackQuotes() {
		return '`';
	}
	
	function getQuotes() {
		return '"';
	}

	function getStandardDbList() {
		return array( 'mysql', 'test' );
	}
	
	function setAuthOptions($options) {
	}

	function connect($ip, $user, $password, $db="") {
		if (!function_exists('mysql_connect')) {
			return $this->error(str_replace('{{NAME}}', 'MySQL', __('{{NAME}} client library is not installed')));
		}
		
		$this->conn = @mysql_connect($ip, $user, $password);
		if (!$this->conn)
			return $this->error(__('Database connection failed to the server'));

		if ($db && !@mysql_select_db($db, $this->conn))
			return $this->error(mysql_error($this->conn));

		$this->ip = $ip;
		$this->user = $user;
		$this->password = $password;
		$this->db = $db;
		
		$this->selectVersion();
		$this->query("SET CHARACTER SET 'utf8'");
		$this->query("SET collation_connection = 'utf8_general_ci'");
		
		return true;
	}

	function disconnect() {
		@mysql_close($this->conn);
		$this->conn = false;
		return true;
	}
	
	function getCurrentUser() {
		if ($this->query('select user()')) {
			$row = $this->fetchRow();
			return $row[0];
		}
		return '';
	}
	
	function selectDb($db) {
		$this->db = $db;
		mysql_select_db($this->db);
	}
	
	function createDatabase( $name ) {
		$sql = "create database `".$this->escape($name)."`";
		return $this->query($sql);
	}
	
	function query($sql, $stack=0) {		// call with query($sql, 1) to store multiple results
		if (!$this->conn) {
			log_message("DB: Connection has been closed");
			return false;
		}

		$this->result[$stack] = "";
		
		//traceMessage("Query: $sql");
		$this->lastQuery = $sql;
		$this->queryTime = $this->getMicroTime();
		$this->result[$stack] = @mysql_query($sql, $this->conn);
		$this->queryTime = $this->getMicroTime() - $this->queryTime;

		if (!$this->result[$stack]) {
			$this->errMsg = mysql_error($this->conn);
			log_message("DB: $sql ::: ".@mysql_error($this->conn));
			return false;
		}
		
		return true;
	}

	function getWarnings() {
		$ret = array();
		$res = mysql_query("SHOW WARNINGS", $this->conn);
		if ($res !== FALSE) {
			while($row = mysql_fetch_array($res))
				$ret[$row['Code']] = $row['Message'];
		}
		return $ret;
	}
	
	function getQueryTime($time=false) {  // returns formatted given value or internal query time
		return sprintf("%.2f", ($time ? $time : $this->queryTime) * 1000) . " ms";
	}
	
	function hasAffectedRows() {
		return ($this->getAffectedRows() > 0);
	}
	
	function insert($table, $values) {
		if (!is_array($values))
			return false;
		
		$sql = "insert into $table (";
		
		foreach($values as $field=>$value)
			$sql .= " $field,";
		
		$sql = substr($sql, 0, strlen($sql) - 1);
		
		$sql .= ") values (";
		
		foreach($values as $field=>$value) {
			if ($this->escapeData)
				$sql .= "'" . $this->escape($value) . "',";
			else
				$sql .= "'$value',";
		}
		
		$sql = substr($sql, 0, strlen($sql) - 1);
		
		$sql .= ")";
		
		$this->query($sql);
	}
	
	function update($table, $values, $condition="") {
		if (!is_array($values))
			return false;
		
		$sql = "update $table set ";
		
		foreach($values as $field=>$value) {
			if ($this->escapeData)
				$sql .= "$field = '" . $this->escape($field) . "',";
			else
				$sql .= "$field = '$value',";
		}
		
		$sql = substr($sql, 0, strlen($sql) - 1);
		
		if ($condition != "")
			$sql .= "$condition";
		
		$this->query($sql);
	}
	
	function getInsertID() {
		return mysql_insert_id($this->conn);
	}
	
	function getResult($stack=0) {
		return $this->result[$stack];
	}
	
	function hasResult($stack=0) {
		return ($this->result[$stack] !== TRUE && $this->result[$stack] !== FALSE);
	}
	
	function fetchRow($stack=0, $type="") {
		if($type == "")
			$type = MYSQL_BOTH;
		else if ($type == "num")
			$type = MYSQL_NUM;
		else if ($type == "assoc")
			$type = MYSQL_ASSOC;

		if (!$this->result[$stack]) {
			log_message("DB: called fetchRow[$stack] but result is false");
			return;
		}
		return @mysql_fetch_array($this->result[$stack], $type);
	}
	
	function fetchSpecificRow($num, $type="", $stack=0) {
		if($type == "")
			$type = MYSQL_BOTH;
		else if ($type == "num")
			$type = MYSQL_NUM;
		else if ($type == "assoc")
			$type = MYSQL_ASSOC;
		
		if (!$this->result[$stack]) {
			log_message("DB: called fetchSpecificRow[$stack] but result is false");
			return;
		}

		mysql_data_seek($this->result[$stack], $num);
		return @mysql_fetch_array($this->result[$stack], $type);
	}
	
	function numRows($stack=0) {
		return mysql_num_rows($this->result[$stack]);
	}
	
	function error($str) {
		log_message("DB: " . $str);
		$this->errMsg = $str;
		return false;
	}
	
	function getError() {
		return $this->errMsg;
	}
	
	function escape($str) {
		return mysql_escape_string($str);
	}
	
	function quote($str) {
		if(strpos($str, '.') === false)
			return '`' . $str . '`';
		return '`' . str_replace('.', '`.`', $str) . '`';
	}
	
	function setEscape($escape=true) {
		$this->escapeData = $escape;
	}

	function getAffectedRows() {
		return mysql_affected_rows($this->conn);
	}
	
	/**************************************/
	function getDatabases() {
		$res = mysql_query("show databases", $this->conn);
		$ret = array();
		while($row = mysql_fetch_array($res))
			$ret[] = $row[0];
		return $ret;
	}
	
	function getTables() {
		if (!$this->db)
			return array();
		$res = mysql_query("show tables", $this->conn);
		$ret = array();
		while($row = mysql_fetch_array($res))
			$ret[] = $row[0];
		return $ret;
	}

	// NOT SUPPORTED IN MYSQL 4 - Function are here for code compatibility reasons only
	function getViews() {
		$ret = array();
		return $ret;
	}
	
	function getProcedures() {
		$ret = array();
		return $ret;
	}
	
	function getFunctions() {
		$ret = array();
		return $ret;
	}
	
	function getTriggers() {
		$ret = array();
		return $ret;
	}
	
	function getEvents() {
		$ret = array();
		return $ret;
	}
	
	/**************************************/
	function getFieldInfo($stack=0) {
		$fields = array();
		$i = 0;
		while ($i < mysql_num_fields($this->result[$stack])) {
			$meta = mysql_fetch_field($this->result[$stack], $i);
			if ($meta) {
				$f = new StdClass;
				$type = mysql_field_type($this->result[$stack], $i);
				$f->name = $meta->name;
				$f->table = $meta->table;
				$f->not_null = $meta->not_null;
				$f->blob = $meta->blob;
				$f->pkey = $meta->primary_key;
				$f->ukey = $meta->unique_key;
				$f->mkey = $meta->multiple_key;
				$f->zerofill = $meta->zerofill;
				$f->unsigned = $meta->unsigned;
				$f->autoinc = 0;//($meta->flags & AUTO_INCREMENT_FLAG) ? 1 : 0;
				$f->numeric = $meta->numeric;

				$f->type = ($type == 'string' ? 'text' : 'binary');
				/*if ($meta->flags & ENUM_FLAG)
					$f->type = 'enum';
				else if ($meta->flags & SET_FLAG)
					$f->type = 'set';
				else if ($meta->flags & BINARY_FLAG)
					$f->type = 'binary';
				else if ($meta->type < 10)
					$f->type = 'numeric';
				else
					$f->type = 'char';*/
				$fields[] = $f;
			}
			$i++;
		}
		return $fields;
	}
	
	function getMicroTime() {
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}
	
	function selectVersion() {
		$res = mysql_query("SHOW VARIABLES LIKE 'version%'", $this->conn);
		while($row = mysql_fetch_array($res)) {
			if ($row[0] == 'version') {
				Session::set('db', 'version', intval($row[1]));
				Session::set('db', 'version_full', $row[1]);
			} else if ($row[0] == 'version_comment') {
				Session::set('db', 'version_comment', $row[1]);
			}
		}
	}
	
	function getCreateCommand($type, $name) {
		$cmd = '';
		$type = $this->escape($type);
		$name = $this->escape($name);

		if ($type != "table")
			return '';

		$sql = "show create $type `$name`";

		if (!$this->query($sql) || $this->numRows() == 0)
			return '';
		
		$row = $this->fetchRow();

		$cmd = $row[1];
		return $cmd;
	}
	
	function getDropCommand( $table ) {
		return "drop table if exists " . $this->quote( $table );
	}
	
	function getFieldValues($table, $name) {
		$sql = 'show full fields from `'.$table.'` where `Field` = \''.$this->escape($name).'\'';
		$res = mysql_query($sql, $this->conn);
		if (mysql_num_rows($res) == 0)
			return ( (object) array('list' => array()) );
		$row = mysql_fetch_array($res);
		$type = $row['Type'];
		preg_match('/enum\((.*)\)$/', $type, $matches);
		if (!isset($matches[1]))
			preg_match('/set\((.*)\)$/', $type, $matches);
		if (isset($matches[1])) {
			$list = explode(',', $matches[1]);
			foreach($list as $k => $v)
				$list[$k] = str_replace("\\'", "'", trim($v, " '"));
			return $list;
		}
		return ( (object) array('list' => array()) );
	}
	
	function getEngines() {
		$sql = 'show engines';
		$res = mysql_query($sql,$this->conn);
		if (mysql_num_rows($res) == 0)
			return ( array() );
		
		$arr = array();
		while($row = mysql_fetch_array($res))
			if ($row['Support'] != 'NO')
				$arr[] = $row['Engine'];
		return $arr;
	}
	
	function getCharsets() {
		$sql = 'show character set';
		$res = mysql_query($sql,$this->conn);
		if (mysql_num_rows($res) == 0)
			return ( array() );
		
		$arr = array();
		while($row = mysql_fetch_array($res))
			$arr[] = $row['Charset'];

		asort($arr);
		return $arr;
	}
	
	function getCollations() {
		$sql = 'show collation';
		$res = mysql_query($sql,$this->conn);
		if (mysql_num_rows($res) == 0)
			return ( array() );
		
		$arr = array();
		while($row = mysql_fetch_array($res))
			$arr[] = $row['Collation'];

		asort($arr);
		return $arr;
	}
	
	function getTableFields($table) {
		$sql = "show full fields from ".$this->quote($table);
			if (!$this->query($sql, "_temp"))
				return array();

		$fields = array();
		while($row = $this->fetchRow("_temp")) {
			$f = new StdClass;
			$f->type = $row['Type'];
			$f->name = $row['Field'];
			$fields[] = $f;
		}

		return $fields;
	}
	
	function getTableProperties($table) {
		$sql = "show table status like '".$this->escape($table)."'";
		if (!$this->query($sql, "_tmp_query"))
			return FALSE;
		return $this->fetchRow("_tmp_query");
	}
	
	function queryTableStatus() {
		$sql = "show table status";
		return $this->query($sql);
	}
	
	function getTableDescription( $table ) {
		$sql = "describe " . $this->quote( $table );
		return $this->query($sql);
	}
	
	function flush($option = '', $skiplog=false) {
		$options = array('HOSTS', 'PRIVILEGES', 'TABLES', 'STATUS', 'DES_KEY_FILE', 'QUERY CACHE', 'USER_RESOURCES', 'TABLES WITH READ LOCK');
		if ($option == '') {
			foreach($options as $option) {
				$sql = "flush " . ( $skiplog ? "NO_WRITE_TO_BINLOG " : "") . $this->escape($option);
				$this->query($sql, '_temp_flush');
			}
			$this->query('UNLOCK TABLES', '_temp_flush');
		} else {
			$sql = "flush " . ( $skiplog ? "NO_WRITE_TO_BINLOG " : "") . $this->escape($option);
			$this->query($sql, '_temp_flush');
			if ($option == 'TABLES WITH READ LOCK')
				$this->query('UNLOCK TABLES', '_temp_flush'); 
		}
		
		return true;
	}
	
	function getLastQuery() {
		return $this->lastQuery;
	}
	
	
	function getInsertStatement($tbl) {
		$sql = "show full fields from `$tbl`";
		if (!$this->query($sql, '_insert'))
			return false;
		
		$str = "INSERT INTO `".$tbl."` (";
		$num = $this->numRows('_insert');
		$row = $this->fetchRow('_insert');
		$str .= "`" . $row[0] . "`";

		if ($row["Extra"] == "auto_increment")
			$str2 = " VALUES (NULL";
		else
			$str2 = " VALUES (\"\"";

		for($i=1; $i<$num; $i++) {
			$row = $this->fetchRow('_insert');
			$str .= ",`" . $row[0] . "`";
			if ($row["Extra"] == "auto_increment")
				$str2 .= ",NULL";
			//else if (strpos($row["Type"], "int") !== false)
			//	$str2 .= ", ";		// for numeric fields
			else
				$str2 .= ",\"\"";
		}

		$str .= ")";
		$str2 .= ")";
		
		return $str.$str2;
	}

	function getUpdateStatement($tbl) {
		$sql = "show full fields from `".$this->escape($tbl)."`";
		if (!$this->query($sql, '_update'))
			return false;

		$pKey = '';  // if a primary key is available, this helps avoid multikey attributes in where clause
		$str2 = "";
		$str = "UPDATE `".$tbl."` SET ";
		$num = $this->numRows('_update');
		$row = $this->fetchRow('_update');

		$str .= "`" . $row[0] . "`=\"\"";
		if ($row["Key"] != "")
				$str2 .= "`$row[0]`=\"\"";
		if ($row["Key"] == 'PRI')
			$pKey = $row[0];
		
		for($i=1; $i<$num; $i++) {
			$row = $this->fetchRow('_update');
			$str .= ",`" . $row[0] . "`=\"\"";
			if ($row["Key"] != "") {
				if ($row["Key"] == 'PRI')
					$pKey = $row[0];
				if ($str2 != "")
					$str2 .= " AND ";
				$str2 .= "`$row[0]`=\"\"";
			}
		}

		// if we found a primary key, then use it only for where clause and discard other keys
		if ($pKey != '')
			$str2 = "`$pKey`=\"\"";
		if ($str2 != "")
			$str2 = " WHERE " . $str2;

		return $str . $str2;
	}
	
	function truncateTable($tbl) {
		return $this->query('truncate table '.$this->quote($tbl));
	}
	
	function renameObject($name, $type, $new_name) {
		$result = false;
		if($type == 'table') {
			$query = 'rename '.$this->escape($type).' `'.$this->escape($name).'` to `'.$this->escape($new_name).'`';
			$result = $this->query($query);
		}
		return $result;
	}
	
	function dropObject($name, $type) {
		$result = false;
		if($type == 'table') {
			$query = 'drop '.$this->escape($type).' `'.$this->escape($name).'`';
			$result = $this->query($query);
		}
		return $result;
	}
	
	function copyObject($name, $type, $new_name) {
		$result = false;
		if($type == 'table') {
			$query = 'create '.$this->escape($type).' `' . $this->escape($new_name) . '` like `' . $this->escape($name) . '`';
			$result = $this->query($query);
			if ($result) {
				$query = 'insert into `' . $this->escape($new_name) . '` select * from `' . $this->escape($name) . '`';
				$result = $this->query($query);
			}
		}
		return $result;
	}
	
	function getAutoIncField($table) {
		$sql = "show full fields from `".$this->escape($table)."`";
			if (!$this->query($sql, "_temp"))
				return false;

		$i = 0;
		while($row = $this->fetchRow("_temp")) {
			if (strpos($row["Extra"], "auto_increment") !== false) {
				return $i;
			}
			$i++;
		}

		return -1;
	}
	
	function queryVariables() {
		return $this->query("SHOW VARIABLES");
	}
	
	function getLimit($count, $offset = 0) {
		return " limit $offset, $count";
	}
	
	function addExportHeader( $db ) {
		$str = "/* Database export results for db ".$db."*/\n";
		$str .= "\n/* Preserve session variables */\nSET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;\nSET FOREIGN_KEY_CHECKS=0;\n\n/* Export data */\n";
		return $str;
	}
	
	function addExportFooter() {
		return "\n/* Restore session variables to original values */\nSET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;\n";
	}
}
?>