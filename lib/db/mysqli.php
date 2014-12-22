<?php
/**
 * This file is a part of MyWebSQL package
 * A simple and easy to debug mysqli wrapper class
 *
 * @file:      lib/db/mysqli.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (defined("CLASS_DB_MYSQLI_INCLUDED"))
	return true;

define("CLASS_DB_MYSQLI_INCLUDED", "1");

define("NOT_NULL_FLAG",         1);         /* Field can't be NULL */
define("PRI_KEY_FLAG",           2);         /* Field is part of a primary key */
define("UNIQUE_KEY_FLAG",        4);         /* Field is part of a unique key */
define("MULTIPLE_KEY_FLAG",      8);         /* Field is part of a key */
define("BLOB_FLAG",            16);         /* Field is a blob */
define("UNSIGNED_FLAG",         32);         /* Field is unsigned */
define("ZEROFILL_FLAG",         64);        /* Field is zerofill */
define("BINARY_FLAG",          128);         /* Field is binary   */
define("ENUM_FLAG",            256);         /* field is an enum */
define("AUTO_INCREMENT_FLAG",  512);         /* field is a autoincrement field */
define("TIMESTAMP_FLAG",      1024);         /* Field is a timestamp */
define("SET_FLAG",            2048);         /* Field is a set */

class DB_Mysqli {
	var $ip, $user, $password, $db;
	var $conn;
	var $result;		// array
	var $errMsg;
	var $escapeData;
	var $lastQuery;
	var $queryTime;

	// these help identify field definitions more clearly
	var $field_types = array(
		7   => 'datetime',
		10  => 'date',
		11  => 'time',
		12  => 'datetime',
		13  => 'year',
		16  => 'bit',
		246 => 'numeric'
	);

	function DB_Mysqli() {
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
			case 'view':
			case 'procedure':
			case 'function':
			case 'trigger':
				return true;
				break;
			case 'event':
				if (  ((float)Session::get('db', 'version_full')) >= 5.1 )
					return true;
				break;
		}
		return false;
	}

	function getObjectTypes() {
		$types = array(
			'tables', 'views', 'procedures', 'functions', 'triggers'
		);

		if ($this->hasObject('event'))
			$types[] = 'events';

		return $types;
	}

	function getObjectList( $details = false ) {
		$data = array(
			'tables' => $this->getTables( $details ),
			'views' => $this->getViews(),
			'procedures' => $this->getProcedures(),
			'functions' => $this->getFunctions(),
			'triggers' => $this->getTriggers(),
		);

		if ($this->hasObject('event'))
			$data['events'] = $this->getEvents();

		return $data;
	}

	function getBackQuotes() {
		return '`';
	}

	function getQuotes() {
		return '"';
	}

	function getStandardDbList() {
		return array( 'information_schema', 'performance_schema', 'mysql', 'test' );
	}

	function setAuthOptions($options) {
	}

	function connect($ip, $user, $password, $db="")	{
		if (!function_exists('mysqli_connect')) {
			return $this->error(str_replace('{{NAME}}', 'MySQLi', __('{{NAME}} client library is not installed')));
		}

		$this->conn = @mysqli_connect($ip, $user, $password);
		if (!$this->conn)
			return $this->error(__('Database connection failed to the server'));

		if ($db && !@mysqli_select_db($this->conn, $db))
			return $this->error(mysqli_error($this->conn));

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
		@mysqli_close($this->conn);
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
		mysqli_select_db($this->conn, $this->db);
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

		if (v($this->result[$stack]))
			@mysqli_free_result($this->result[$stack]);

		$this->result[$stack] = "";

		$this->lastQuery = $sql;
		$this->queryTime = $this->getMicroTime();
		$this->result[$stack] = @mysqli_query($this->conn, $sql);
		$this->queryTime = $this->getMicroTime() - $this->queryTime;

		if ($this->result[$stack] === FALSE) {
			$this->errMsg = mysqli_error($this->conn);
			log_message("DB: $sql ::: ".@mysqli_error($this->conn));
			return false;
		}

		return true;
	}

	function getWarnings() {
		$ret = array();
		$res = mysqli_query($this->conn, "SHOW WARNINGS");
		if ($res !== FALSE) {
			while($row = mysqli_fetch_array($res))
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
		return mysqli_insert_id($this->conn);
	}

	function getResult($stack=0) {
		return $this->result[$stack];
	}

	function hasResult($stack=0) {
		return (is_object($this->result[$stack]));	// !== FALSE && $this->result[$stack] !== TRUE);
	}

	function fetchRow($stack=0, $type="") {
		if($type == "")
			$type = MYSQLI_BOTH;
		else if ($type == "num")
			$type = MYSQLI_NUM;
		else if ($type == "assoc")
			$type = MYSQLI_ASSOC;

		if (!$this->result[$stack]) {
			log_message("DB: called fetchRow[$stack] but result is false");
			return;
		}
		return @mysqli_fetch_array($this->result[$stack], $type);
	}

	function fetchSpecificRow($num, $type="", $stack=0) {
		if($type == "")
			$type = MYSQL_BOTH;
		else if ($type == "num")
			$type = MYSQLI_NUM;
		else if ($type == "assoc")
			$type = MYSQLI_ASSOC;

		if (!$this->result[$stack]) {
			log_message("DB: called fetchSpecificRow[$stack] but result is false");
			return;
		}

		mysqli_data_seek($this->result[$stack], $num);
		return @mysqli_fetch_array($this->result[$stack], $type);
	}

	function numRows($stack=0) {
		return mysqli_num_rows($this->result[$stack]);
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
		return mysqli_escape_string($this->conn, $str);
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
		return mysqli_affected_rows($this->conn);
	}

	/**************************************/
	function getDatabases() {
		$res = mysqli_query($this->conn, "show databases");
		$ret = array();
		while($row = mysqli_fetch_array($res))
			$ret[] = $row[0];
		return $ret;
	}

	function getTables( $details = false ) {
		if (!$this->db)
			return array();
		$res = mysqli_query($this->conn, "show table status from `$this->db` where engine is NOT null");
		$ret = array();
		while($row = mysqli_fetch_array($res,  MYSQLI_ASSOC)) {
			$ret[] = $details ?	array(
				$row['Name'], // table name
				$row['Rows'], // number of records,
				$row['Data_length'] + $row['Index_length'], // size of the table
				(empty($row['Update_time']) ? $row['Create_time'] : $row['Update_time'] ), // last update timestamp
			) : $row['Name'];
		}
		return $ret;
	}

	function getViews() {
		if (!$this->db)
			return array();
		$res = mysqli_query($this->conn, "show table status from `$this->db` where engine is null");
		if (!$res)
			return array();
		$ret = array();
		while($row = mysqli_fetch_array($res))
			$ret[] = $row[0];
		return $ret;
	}

	function getProcedures() {
		if (!$this->db)
			return array();
		$res = mysqli_query($this->conn, "show procedure status where db = '$this->db'");
		if (!$res)
			return array();
		$ret = array();
		while($row = mysqli_fetch_array($res))
			$ret[] = $row[1];
		return $ret;
	}

	function getFunctions() {
		if (!$this->db)
			return array();
		$res = mysqli_query($this->conn, "show function status where db = '$this->db'");
		if (!$res)
			return array();
		$ret = array();
		while($row = mysqli_fetch_array($res))
			$ret[] = $row[1];
		return $ret;
	}

	function getTriggers() {
		if (!$this->db)
			return array();
		$res = mysqli_query($this->conn, "select `TRIGGER_NAME` from `INFORMATION_SCHEMA`.`TRIGGERS` where `TRIGGER_SCHEMA` = '$this->db'");
		if (!$res)
			return array();
		$ret = array();
		while($row = mysqli_fetch_array($res))
			$ret[] = $row[0];
		return $ret;
	}

	function getEvents() {
		if (!$this->db)
			return array();
		$res = mysqli_query($this->conn, "select `EVENT_NAME` from `INFORMATION_SCHEMA`.`EVENTS` where `EVENT_SCHEMA` = '$this->db'");
		if (!$res)
			return array();
		$ret = array();
		while($row = mysqli_fetch_array($res))
			$ret[] = $row[0];
		return $ret;
	}

	/**************************************/
	function getFieldInfo($stack=0) {
		$fields = array();
		$i = 0;
		while ($i < mysqli_num_fields($this->result[$stack])) {
			$meta = mysqli_fetch_field_direct($this->result[$stack], $i);
			if ($meta) {
				$f = new StdClass;
				$f->name = $meta->name;
				$f->table = $meta->table;
				$f->not_null = ($meta->flags & NOT_NULL_FLAG) ? 1 : 0;
				$f->blob = ($meta->flags & BLOB_FLAG) ? 1 : 0;
				$f->pkey = ($meta->flags & PRI_KEY_FLAG) ? 1 : 0;
				$f->ukey = ($meta->flags & UNIQUE_KEY_FLAG) ? 1 : 0;
				$f->mkey = ($meta->flags & MULTIPLE_KEY_FLAG) ? 1 : 0;
				$f->zerofill = ($meta->flags & ZEROFILL_FLAG) ? 1 : 0;
				$f->unsigned = ($meta->flags & UNSIGNED_FLAG) ? 1 : 0;
				$f->autoinc = ($meta->flags & AUTO_INCREMENT_FLAG) ? 1 : 0;
				$f->numeric = $meta->type < 10 ? 1 : 0;
				if (isset($this->field_types[$meta->type]))
					$f->type = $this->field_types[$meta->type];
				else if ($meta->flags & ENUM_FLAG)
					$f->type = 'enum';
				else if ($meta->flags & SET_FLAG)
					$f->type = 'set';
				else if ($meta->flags & TIMESTAMP_FLAG)
					$f->type = "timestamp";
				else if ($meta->flags & BINARY_FLAG)
					$f->type = 'binary';
				else if ($meta->type < 10)
					$f->type = 'numeric';
				else
					$f->type = 'text';
				
				if ($f->type == 'enum' || $f->type == 'set')
					$f->list = $this->getFieldValues($f->table, $f->name);
				else if ($f->type == 'bit')
					$f->list = array('0', '1');
					
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
		$res = mysqli_query($this->conn, "SHOW VARIABLES LIKE 'version%'");
		while($row = mysqli_fetch_array($res)) {
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

		if ($type == "trigger")
			$sql = "show triggers where `trigger` = '$name'";
		else
			$sql = "show create $type `$name`";

		if (!$this->query($sql) || $this->numRows() == 0)
			return '';

		$row = $this->fetchRow();

		if ($type == "trigger")
			$cmd = "create trigger `$row[0]`\r\n$row[4] $row[1] on `$row[2]`\r\nfor each row\r\n$row[3]";
		else {
			switch($type) {
				case 'table':
				case 'view':
					$cmd = $row[1];
					break;
				case 'procedure':
				case 'function':
					$cmd = $row[2];
					break;
				case 'event':
					$cmd = $row[3];
					break;
			}
		}
		return $cmd;
	}

	function getDropCommand( $table ) {
		return "drop table if exists " . $this->quote( $table );
	}

	function getTruncateCommand( $table ) {
		return 'truncate table ' . $this->quote( $table );
	}

	function getFieldValues($table, $name) {
		$sql = 'show full fields from `'.$table.'` where `Field` = \''.$this->escape($name).'\'';
		$res = mysqli_query($this->conn, $sql);
		if (mysqli_num_rows($res) == 0)
			return ( (object) array('list' => array()) );
		$row = mysqli_fetch_array($res);
		$type = $row['Type'];
		preg_match('/enum\((.*)\)$/', $type, $matches);
		if (!isset($matches[1]))
			preg_match('/set\((.*)\)$/', $type, $matches);
		if (isset($matches[1])) {
			if (phpCheck(5.3)) {
				$regex = "/\('(.*)'\)/";
				preg_match_all($regex, $row['Type'], $list);
				return array_map('replace_single_quotes', explode("','", $list[1][0]));
			} else {
				$list = explode(',', $matches[1]);
				foreach($list as $k => $v)
					$list[$k] = str_replace("\\'", "'", trim($v, " '"));
				return $list;
			}
		}
		return ( (object) array('list' => array()) );
	}

	function getEngines() {
		$sql = 'show engines';
		$res = mysqli_query($this->conn, $sql);
		if (mysqli_num_rows($res) == 0)
			return ( array() );

		$arr = array();
		while($row = mysqli_fetch_array($res))
			if ($row['Support'] != 'NO')
				$arr[] = $row['Engine'];
		return $arr;
	}

	function getCharsets() {
		$sql = 'show character set';
		$res = mysqli_query($this->conn, $sql);
		if (mysqli_num_rows($res) == 0)
			return ( array() );

		$arr = array();
		while($row = mysqli_fetch_array($res))
			$arr[] = $row['Charset'];

		asort($arr);
		return $arr;
	}

	function getCollations() {
		$sql = 'show collation';
		$res = mysqli_query($this->conn, $sql);
		if (mysqli_num_rows($res) == 0)
			return ( array() );

		$arr = array();
		while($row = mysqli_fetch_array($res))
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
		$sql = "show table status where `Name` like '".$this->escape($table)."'";
		if (!$this->query($sql, "_tmp_query"))
			return FALSE;
		return $this->fetchRow("_tmp_query");
	}

	function queryTableStatus() {
		$sql = "show table status where Engine is not null";
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
		else {
			$command = $this->getCreateCommand($type, $name);
			$search = '/(create.*'.$type. ' )('.$name.'|\`'.$name.'\`)/i';
			$replace = '${1} `'.$new_name.'`';
			$query = preg_replace($search, $replace, $command, 1);
			if ($this->query($query)) {
				$query = 'drop '.$this->escape($type).' `'.$this->escape($name).'`';
				$result = $this->query($query);
			}
		}

		return $result;
	}

	function dropObject($name, $type) {
		$result = false;
		$query = 'drop '.$this->escape($type).' `'.$this->escape($name).'`';
		$result = $this->query($query);
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
		else {
			$command = $this->getCreateCommand($type, $name);
			$search = '/(create.*'.$type. ' )('.$name.'|\`'.$name.'\`)/i';
			$replace = '${1} `'.$new_name.'`';
			$query = preg_replace($search, $replace, $command, 1);
			$result = $this->query($query);
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

	function addExportHeader( $name, $obj = 'db', $type='insert' ) {
		$str = '';
		switch($type) {
			case 'insert':
				if ( $obj == 'db' ) {
					$str = "/* Database export results for db ".$name." */\n";
					$str .= "\n/* Preserve session variables */\nSET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;\nSET FOREIGN_KEY_CHECKS=0;\n\n/* Export data */\n";
				} else if ( $obj == 'table' ) {
					$str = "/* Table data export for table ".$name." */\n";
					$str .= "\n/* Preserve session variables */\nSET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;\nSET FOREIGN_KEY_CHECKS=0;\n\n/* Export data */\n";
				} else if ( $obj == 'query' ) {
					$str = "/* Export results for query data */\n";
					$str .= "/* Query: \n".$name."\n*/\n";
					$str .= "\n/* Preserve session variables */\nSET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;\nSET FOREIGN_KEY_CHECKS=0;\n\n/* Export data */\n";
				}
			break;
		}
		return $str;
	}

	function addExportFooter( $type='insert' ) {
		if ($type == 'insert')
			return "\n/* Restore session variables to original values */\nSET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;\n";
		return '';
	}

	function set_constraint( $constraint, $value ) {
		switch ($constraint) {
			case 'fkey':
				$this->query('SET FOREIGN_KEY_CHECKS=' . ($value ? '1' : '0') );
			break;
		}
	}
}
?>