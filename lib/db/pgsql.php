<?php
/**
 * This file is a part of MyWebSQL package
 * A very simple to use and easy to debug postgres wrapper class
 *
 * @file:      lib/db/pgsql.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (defined("CLASS_DB_PGSQL_INCLUDED"))
	return true;

define("CLASS_DB_PGSQL_INCLUDED", "1");
/*
define("NOT_NULL_FLAG",         1);         //* Field can't be NULL //* /
define("PRI_KEY_FLAG",           2);         //* Field is part of a primary key //* /
define("UNIQUE_KEY_FLAG",        4);         //* Field is part of a unique key //* /
define("MULTIPLE_KEY_FLAG",      8);         //* Field is part of a key //* /
define("BLOB_FLAG",            16);         //* Field is a blob //* /
define("UNSIGNED_FLAG",         32);         //* Field is unsigned //* /
define("ZEROFILL_FLAG",         64);        //* Field is zerofill //* /
define("BINARY_FLAG",          128);         //* Field is binary   //* /
define("ENUM_FLAG",            256);        //* field is an enum //* /
define("AUTO_INCREMENT_FLAG",  512);        //* field is a autoincrement field //* /
define("TIMESTAMP_FLAG",      1024);        //* Field is a timestamp //* / 
define("SET_FLAG",            2048);        //* Field is a set //* / 
*/
class DB_Pgsql {
	var $ip, $user, $password, $db;
	var $conn;
	var $result;
	var $errMsg;
	var $escapeData;
	var $lastQuery;
	var $queryTime;
	var $conn_str;
	var $stack_last;  // we need it for getting affected rows
	var $includeStandardObjects;

	function DB_Pgsql() {
		$this->conn = null;
		$this->errMsg = null;
		$this->escapeData = true;
		$this->result = array();
		$this->includeStandardObjects = false;
	}

	function name() {
		return 'pgsql';
	}
	
	function hasServer() {
		return true;
	}
	
	function hasObject($type) {
		switch($type) {
			case 'table':
			case 'view':
			//case 'procedure':
			case 'function':
			case 'trigger':
			case 'sequence':
			case 'template':
			case 'schema':
				return true;
				break;
			case 'event':
				//if (  ((float)Session::get('db', 'version_full')) >= 5.1 )
				//	return true;
				break;
		}
		return false;
	}
	
	function getObjectTypes() {
		$types = array(
			'schemas', 'tables', 'views',  'functions', 'triggers'
		);
		
		if ($this->hasObject('event'))
			$types[] = 'events';
	
		return $types;
	}
	
	
	function getObjectList() {
		$data = array(
			'schemas' => $this->getSchemas(),
			'tables' => $this->getTables(),
			'views' => $this->getViews(),
			'functions' => $this->getFunctions(),
			'triggers' => $this->getTriggers(),
		);
		
		if ($this->hasObject('event'))
			$data['events'] = $this->getEvents();
	
		return $data;
	}
	
	function getBackQuotes() {
		return '"';
	}
	
	function getQuotes() {
		return "'";
	}

	function getStandardDbList() {
		return array( 'postgres', 'test' );
	}
	
	function setAuthOptions($options) {
	}

	function connect($ip, $user, $password, $db="")	{
		if (!function_exists('pg_connect')) {
			return $this->error(str_replace('{{NAME}}', 'PGSQL', __('{{NAME}} client library is not installed')));
		}
		
		$this->conn_str = $this->build_conn_string($ip, $user, $password, $db);
		$this->conn = @pg_connect($this->conn_str);
		if (!$this->conn)
			return $this->error(__('Database connection failed to the server'));
		
		$this->ip = $ip;
		$this->user = $user;
		$this->password = $password;
		$this->db = $db;
		
		$this->selectVersion();
		$this->query("SET CLIENT_ENCODING to 'utf8'");
		$this->query("SET NAMES 'utf8'");
		
		return true;
	}

	function disconnect() {
		@pg_close($this->conn);
		$this->conn = false;
		return true;
	}
	
	function getCurrentUser() {
		if ($this->query('select user')) {
			$row = $this->fetchRow();
			return $row[0];
		}
		return '';
	}
	
	function selectDb($db) {
		$this->db = $db;
		//$this->conn_str = $this->build_conn_string($this->ip, $user, $password, $db);
		@pg_connect("dbname=" . pg_escape_string($db) );
	}
	
	function createDatabase( $name ) {
		$sql = "create database \"".$this->escape($name)."\"";
		return $this->query($sql);
	}
	
	function query($sql, $stack=0) {		// call with query($sql, 1) to store multiple results
		if (!$this->conn) {
			log_message("DB: Connection has been closed");
			return false;
		}
	
		if (v($this->result[$stack]))
			@pg_free_result($this->result[$stack]);

		$this->result[$stack] = "";
		$this->stack_last = $stack;
		
		$this->lastQuery = $sql;
		$this->queryTime = $this->getMicroTime();

		$this->result[$stack] = @pg_query($this->conn, $sql);
		$this->queryTime = $this->getMicroTime() - $this->queryTime;
		
		if ($this->result[$stack] === FALSE) {
			$this->errMsg = pg_errormessage($this->conn);
			log_message("DB: $sql ::: ".@pg_errormessage($this->conn));
			return false;
		}
		
		return true;
	}

	function getWarnings() {
		$ret = array();
		/*$res = pg_query($this->conn, "SHOW WARNINGS");
		if ($res !== FALSE) {
			while($row = pg_fetch_array($res))
				$ret[$row['Code']] = $row['Message'];
		}*/
		$ret[] = pg_errormessage($this->conn);
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
		return pg_getlastoid($this->result[$this->stack_last]);
	}
	
	function getResult($stack=0) {
		return $this->result[$stack];
	}
	
	function hasResult($stack=0) {
		return (@pg_result_status($this->result[$stack]) == PGSQL_TUPLES_OK);
	}
	
	function fetchRow($stack=0, $type="") {
		if($type == "")
			$type = PGSQL_BOTH;
		else if ($type == "num")
			$type = PGSQL_NUM;
		else if ($type == "assoc")
			$type = PGSQL_ASSOC;
			
		if (!$this->result[$stack]) {
			log_message("DB: called fetchRow[$stack] but result is false");
			return;
		}
		return @pg_fetch_array($this->result[$stack], -1, $type);
	}
	
	function fetchSpecificRow($num, $type="", $stack=0) {
		if($type == "")
			$type = PGSQL_BOTH;
		else if ($type == "num")
			$type = PGSQL_NUM;
		else if ($type == "assoc")
			$type = PGSQL_ASSOC;
		
		if (!$this->result[$stack]) {
			log_message("DB: called fetchSpecificRow[$stack] but result is false");
			return;
		}
		
		return @pg_fetch_array($this->result[$stack], $num, $type);
	}
	
	function numRows($stack=0) {
		return pg_numrows($this->result[$stack]);
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
		return pg_escape_string($this->conn, $str);
	}
	
	function quote($str) {
		if(strpos($str, '.') === false)
			return '"' . $str . '"';
		return '"' . str_replace('.', '"."', $str) . '"';
	}
	
	function setEscape($escape=true) {
		$this->escapeData = $escape;
	}

	function getAffectedRows() {
		return pg_affected_rows( $this->result[$this->stack_last] );
	}
	
	/**************************************/
	function getDatabases() {
		$res = pg_query($this->conn, "SELECT datname FROM pg_database WHERE NOT datistemplate ORDER BY datname");
		$ret = array();
		while($row = pg_fetch_array($res))
			$ret[] = $row[0];
		return $ret;
	}
	
	function getSchemas() {
		if (!$this->db)
			return array();
		$extra = $this->includeStandardObjects ? "" : "WHERE nspname NOT LIKE 'pg@_%' ESCAPE '@' AND nspname != 'information_schema'";
		$res = pg_query($this->conn, "SELECT pn.nspname, pu.rolname AS nspowner, pg_catalog.obj_description(pn.oid, 'pg_namespace') AS nspcomment FROM pg_catalog.pg_namespace pn LEFT JOIN pg_catalog.pg_roles pu ON (pn.nspowner = pu.oid) $extra ORDER BY nspname");
		$ret = array();
		while($row = pg_fetch_array($res))
			$ret[] = $row[0];
		return $ret;
	}
	
	function getTables() {
		if (!$this->db)
			return array();
		$extra = $this->includeStandardObjects ? "" : "AND table_schema NOT LIKE 'pg@_%' ESCAPE '@' AND table_schema != 'information_schema'";
		$res = pg_query($this->conn, "SELECT table_schema, table_name FROM information_schema.tables WHERE table_type = 'BASE TABLE' $extra ORDER BY table_name");
		$ret = array();
		while($row = pg_fetch_array($res)) {
			$schema = $row[0];
			if (!isset($ret[$schema]))
				$ret[$schema] = array();
			$ret[$schema][] = $row[1];
		}
		return $ret;
	}
	
	function getViews() {
		if (!$this->db)
			return array();
		$extra = $this->includeStandardObjects ? "" : "AND table_schema NOT LIKE 'pg@_%' ESCAPE '@' AND table_schema != 'information_schema'";
		$res = pg_query($this->conn, "SELECT table_schema, table_name FROM information_schema.tables WHERE table_schema = current_schema() and table_type = 'VIEW' $extra ORDER BY table_name");
		if (!$res)
			return array();
		$ret = array();
		while($row = pg_fetch_array($res)) {
			$schema = $row[0];
			if (!isset($ret[$schema]))
				$ret[$schema] = array();
			$ret[$schema][] = $row[1];
		}
		return $ret;
	}
	
	function getFunctions() {
		if (!$this->db)
			return array();
		$extra = $this->includeStandardObjects ? "" : "WHERE n.nspname NOT LIKE 'pg@_%' ESCAPE '@' AND n.nspname != 'information_schema'";
		$res = pg_query($this->conn, "SELECT n.nspname, p.proname AS name FROM pg_proc p INNER JOIN pg_namespace n ON p.pronamespace = n.oid LEFT OUTER JOIN pg_roles u ON u.oid = p.proowner $extra ORDER BY p.proname, n.nspname");
		if (!$res)
			return array();
		$ret = array();
		while($row = pg_fetch_array($res)) {
			$schema = $row[0];
			if (!isset($ret[$schema]))
				$ret[$schema] = array();
			$ret[$schema][] = $row[1];
		}
		return $ret;
	}
	
	function getTriggers() {
		if (!$this->db)
			return array();
		$extra = $this->includeStandardObjects ? "" : "AND n.nspname NOT LIKE 'pg@_%' ESCAPE '@' AND n.nspname != 'information_schema'";
		$res = pg_query($this->conn, "SELECT n.nspname, tgname FROM pg_trigger t INNER JOIN pg_class c ON t.tgrelid = c.oid INNER JOIN pg_namespace n ON c.relnamespace = n.oid WHERE t.tgisinternal = 'f' $extra ORDER BY t.tgname");
		if (!$res)
			return array();
		$ret = array();
		while($row = pg_fetch_array($res)) {
			$schema = $row[0];
			if (!isset($ret[$schema]))
				$ret[$schema] = array();
			$ret[$schema][] = $row[1];
		}
		return $ret;
	}
	
	function getSequences() {
		if (!$this->db)
			return array();
		$extra = $this->includeStandardObjects ? "" : "AND n.nspname NOT LIKE 'pg@_%' ESCAPE '@' AND n.nspname != 'information_schema'";
		$res = pg_query($this->conn, "SELECT n.nspname, c.relname AS name, ds.description, n.nspname, d.refobjid as owntab, u.rolname AS usename FROM pg_class c LEFT OUTER JOIN pg_roles u ON u.oid = c.relowner INNER JOIN pg_namespace n ON c.relnamespace = n.oid LEFT OUTER JOIN pg_depend d on c.relkind = 'S' and d.classid = c.tableoid and d.objid = c.oid and d.objsubid = 0 and d.refclassid = c.tableoid and d.deptype = 'i' LEFT OUTER JOIN pg_description ds ON c.oid = ds.objoid WHERE c.relkind = 'S' $extra ORDER BY c.relname");
		if (!$res)
			return array();
		$ret = array();
		while($row = pg_fetch_array($res)) {
			$schema = $row[0];
			if (!isset($ret[$schema]))
				$ret[$schema] = array();
			$ret[$schema][] = $row[1];
		}
		return $ret;
	}
	
	/**************************************/
	function getFieldInfo($stack=0) {

		$fields = array();
		$tables = array();

		$num_fields = pg_num_fields($this->result[$stack]);
		for($i=0; $i<$num_fields; $i++) {
			$f = new StdClass;
			$f->name = pg_field_name($this->result[$stack], $i);
			$f->table = pg_field_table($this->result[$stack], $i);
			$f->type = pg_field_type($this->result[$stack], $i);
			$f->blob = 0;
			$f->pkey = 0;
			$f->ukey = 0;
			$f->mkey = 0;
			//$f->zerofill = ($meta->flags & ZEROFILL_FLAG) ? 1 : 0;
			//$f->unsigned = ($meta->flags & UNSIGNED_FLAG) ? 1 : 0;
			//$f->autoinc = ($meta->flags & AUTO_INCREMENT_FLAG) ? 1 : 0;
			$f->numeric = substr($f->type, 0, 3) == 'int' ? 1 : 0;
			/*if ($meta->flags & ENUM_FLAG)
				$f->type = 'enum';
			else if ($meta->flags & SET_FLAG)
				$f->type = 'set';
			else if ($meta->flags & BINARY_FLAG)
				$f->type = 'binary';
			else if ($meta->type < 10)
				$f->type = 'numeric';
			else
				$f->type = 'char';
			if ($f->type == 'enum' || $f->type == 'set')
				$f->list = $this->getFieldValues($f->table, $f->name);
			 */
			if (!isset($tables[$f->table]))
				$tables[$f->table] = array();
			$tables[$f->table][] = "'".$f->name."'";
			$fields[] = $f;
		}
		
		$this->getFieldMetaInfo($fields, $tables);
		$this->getFieldConstraints($fields, $tables);
		return $fields;
	}
	
	function getMicroTime() {
	   list($usec, $sec) = explode(" ",microtime());
	   return ((float)$usec + (float)$sec);
	}
	
	function selectVersion() {
		$res = pg_query($this->conn, "select version()");
		$row = pg_fetch_array($res);
		preg_match('/([PostgreSQL]+)[\s]+([0-9.]+)[,\s]+(.*)/i', $row[0], $matches);
		Session::set('db', 'version', intval($matches[2]));
		Session::set('db', 'version_full', $matches[2]);
		Session::set('db', 'version_comment', $matches[3]);
	}
	
	function getCreateCommand($type, $name) {
		$func = 'getCreateCommandFor' . $type;
		
		if (method_exists($this, $func))
			return $this->$func( $name );
	
		return '';
		//return 'Create command is not available for the given object type';
		
	}
	
	function getDropCommand( $table ) {
		return "drop table if exists " . $this->quote( $table );
	}
	
	function getFieldValues($table, $name) {
		$sql = 'show full fields from "'.$table.'" where "Field" = \''.$this->escape($name).'\'';
		$res = pg_query($this->conn, $sql);
		if (pg_numrows($res) == 0)
			return ( (object) array('list' => array()) );
		$row = pg_fetch_array($res);
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
		$res = pg_query($this->conn, $sql);
		if (pg_numrows($res) == 0)
			return ( array() );
		
		$arr = array();
		while($row = pg_fetch_array($res))
			if ($row['Support'] != 'NO')
				$arr[] = $row['Engine'];
		return $arr;
	}
	
	function getCharsets() {
		$sql = 'show character set';
		$res = pg_query($this->conn, $sql);
		if (pg_numrows($res) == 0)
			return ( array() );
		
		$arr = array();
		while($row = pg_fetch_array($res))
			$arr[] = $row['Charset'];

		asort($arr);
		return $arr;
	}
	
	function getCollations() {
		$sql = 'show collation';
		$res = pg_query($this->conn, $sql);
		if (pg_numrows($res) == 0)
			return ( array() );
		
		$arr = array();
		while($row = pg_fetch_array($res))
			$arr[] = $row['Collation'];

		asort($arr);
		return $arr;
	}
	
	function getTableFields($table) {
		$fields = array();
		$tables = array();
		$tables[$table] = array();
		$this->getFieldMetaInfo($fields, $tables);
		
		return $fields;
	}
	
	function getTableProperties($table) {
		$sql = "show table status where \"Name\" like '".$this->escape($table)."'";
		if (!$this->query($sql, "_tmp_query"))
			return FALSE;
		return $this->fetchRow("_tmp_query");
	}
	
	function queryTableStatus() {
		$sql = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE table_type = 'BASE TABLE' ORDER BY table_name";
		return $this->query($sql);
	}
	
	function getTableDescription( $table ) {
		list($schema, $tbl) = strpos($table, '.') === FALSE ? array('', $table) : explode('.', $table);
		$sql = "select * from INFORMATION_SCHEMA.COLUMNS where table_schema = '" . $this->escape( $schema ) . "' and table_name = '". $this->escape( $tbl ) . "' order by ordinal_position";
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
		$fields = array();
		$tables = array();
		$tables[$tbl] = array();
		$this->getFieldMetaInfo($fields, $tables);
		
		$str = "INSERT INTO ".$this->quote($tbl)." (";
		$str2 = " VALUES (";
		$num = count($fields);

		for($i=0; $i<$num; $i++) {
			$str .= ($i==0 ? "\"" : ",\"") . $fields[$i]->name . "\"";
			if ($fields[$i]->autoinc) {
				$str2 .= $i==0 ? 'NULL' : ',NULL';
			}
			else if ( $fields[$i]->numeric ) {
				$str2 .= $i==0 ? "0" : ", 0";
			}
			else {
				$str2 .= $i==0 ? "''" : ",''";
			}
		}

		$str .= ")";
		$str2 .= ")";
		
		return $str.$str2;
	}
	
	function getUpdateStatement($tbl) {
		$fields = array();
		$tables = array();
		$tables[$tbl] = array();
		$this->getFieldMetaInfo($fields, $tables);
		$this->getFieldConstraints($fields, $tables);

		$pKey = '';  // if a primary key is available, this helps avoid multikey attributes in where clause
		$str2 = "";
		$str = "UPDATE ".$this->quote($tbl)." SET ";
		$num = count($fields);
		
		for($i=0; $i<$num; $i++) {
			$str .= ($i==0 ? "\"" : ", \"") . $fields[$i]->name . "\"=''";
			if ($fields[$i]->pkey)
				$pKey = $fields[$i]->name;
			
			if ($str2 != "")
				$str2 .= " AND ";
			
			$str2 .= "\"".$fields[$i]->name."\"=''";
		}

		// if we found a primary key, then use it only for where clause and discard other keys
		if ($pKey != '')
			$str2 = "\"$pKey\"=''";
		if ($str2 != "")
			$str2 = " WHERE " . $str2;

		return $str . $str2;
	}
	
	function truncateTable($tbl) {
		return $this->query('truncate table '.$this->quote($tbl));
	}
	
	function renameObject($name, $type, $new_name) {
		$result = false;
		list($schema, $new_name) = $this->splitObjectName( $new_name );
		
		if($type == 'table') {
			$query = 'alter '.$this->escape($type).' ' . $this->quote($name) . ' rename to '.$this->quote($new_name);
			$result = $this->query($query);
		}
		else {
			$func = 'rename' . $type;
			if (method_exists($this, $func))
				return $this->$func( $name );
		}
		
		return $result;
	}
	
	function dropObject($name, $type) {
		$result = false;
		$query = 'drop '.$this->escape($type).' '.$this->quote($name);
		$result = $this->query($query);
		return $result;
	}
	
	function copyObject($name, $type, $new_name) {
		$result = false;
		if($type == 'table') {
			$query = 'create '.$this->escape($type). ' ' . $this->quote($new_name) . ' (like ' . $this->quote($name) . ")";
			$result = $this->query($query);
			if ($result) {
				$query = 'insert into ' . $this->quote($new_name) . ' select * from ' . $this->quote($name);
				$result = $this->query($query);
			}
		}
		else {
			$command = $this->getCreateCommand($type, $name);
			$search = '/(create.*'.$type. ' )('.$name.'|\"'.$name.'\")/i';
			$replace = '${1} "'.$new_name.'"';
			$query = preg_replace($search, $replace, $command, 1);
			$result = $this->query($query);
		}
		return $result;
	}
	
	function getAutoIncField($table) {
		$sql = "show full fields from \"".$this->escape($table)."\"";
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
		return $this->query("SHOW ALL");
	}
	
	function getLimit($count, $offset = 0) {
		return " limit $count offset $offset";
	}
	
	function addExportHeader( $db ) {
		$str = "/* Database export results for db ".$db."*/\n";
		$str .= "\n/* Preserve session variables */\nSET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;\nSET FOREIGN_KEY_CHECKS=0;\n\n/* Export data */\n";
		return $str;
	}
	
	function addExportFooter() {
		return "\n/* Restore session variables to original values */\nSET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;\n";
	}
	
	/***** private functions ******/
	
	protected function splitObjectName( $obj ) {
		return strpos($obj, '.') === FALSE ? array('', $obj) : explode('.', $obj);
	}
	
	// builds connection string for pgsql connect method from parameters
	protected function build_conn_string($ip, $user, $password, $db) {
		$host = $ip;
		$port = '';
		if (strpos($ip, ':') !== false) {
			list($host, $port) = explode(':', $ip);
		}
		$str = "host=" . pg_escape_string($host) . " user=" . pg_escape_string($user);
		$str .= " password=" . pg_escape_string($password);
		if( !empty($port) )
			$str .= " port=" . pg_escape_string($port);
		if( !empty($db) )
			$str .= " dbname=" . pg_escape_string($db);
		
		return $str;
	}
	
	protected function getFieldMetaInfo(&$fields, $tables) {
		
		$sql = "select table_name, column_name, is_nullable, column_default, data_type from information_schema.columns where ";
		foreach($tables as $table => $keys) {
			list($schema, $tbl) = strpos($table, '.') === FALSE ? array('', $table) : explode('.', $table);
			$sql .= "(\"table_schema\"='".trim($schema, '"\' ')."' and \"table_name\"='".trim($tbl, '"\' ')."'";
			if (count($keys) > 0)
				$sql .= " and \"column_name\" in (".implode(',', $keys).")) OR ";
			else
				$sql .= ") OR ";
		}
		$sql = substr($sql, 0, -3) . ' order by ordinal_position';
		$res = pg_query($this->conn, $sql);
		if (!$res)
			return;
		while($row = pg_fetch_array($res)) {
			$found = false;
			for($i=0; $i<count($fields); $i++) {
				if($fields[$i]->table == $row[0] && $fields[$i]->name == $row[1]) {
					$fields[$i]->not_null = $row[2] == "YES" ? 0 : 1;
					$fields[$i]->datatype = $row[4];
					$fields[$i]->default = $row[3];
					$found = true;
					break;
				}
			}
			
			if (!$found) {
				// create new field info
				$f= new StdClass;
				$f->name = $row[1];
				$f->table = $row[0];
				$f->type = $row[4];
				$f->blob = 0;
				$f->pkey = 0;
				$f->ukey = 0;
				$f->mkey = 0;
				$f->not_null = $row[2] == "YES" ? 0 : 1;
				$f->datatype = $row[4];
				$f->default = $row[3];
				//$f->zerofill = ($meta->flags & ZEROFILL_FLAG) ? 1 : 0;
				//$f->unsigned = ($meta->flags & UNSIGNED_FLAG) ? 1 : 0;
				$f->autoinc = 0;
				$f->numeric = substr($f->type, 0, 3) == 'int' ? 1 : 0;
				/*if ($meta->flags & ENUM_FLAG)
					$f->type = 'enum';
				else if ($meta->flags & SET_FLAG)
					$f->type = 'set';
				else if ($meta->flags & BINARY_FLAG)
					$f->type = 'binary';
				else if ($meta->type < 10)
					$f->type = 'numeric';
				else
					$f->type = 'char';
				if ($f->type == 'enum' || $f->type == 'set')
					$f->list = $this->getFieldValues($f->table, $f->name);
				 */
				 $fields[] = $f;
			}
		}
	}

	protected function getFieldConstraints(&$fields, $tables) {
		$sql = "select c.contype as type, ns1.nspname as schema, r1.relname as table, f1.attname as field
			FROM
		   pg_catalog.pg_constraint AS c
		   JOIN pg_catalog.pg_class AS r1 ON (c.conrelid=r1.oid)
		   JOIN pg_catalog.pg_attribute AS f1 ON (f1.attrelid=r1.oid AND (f1.attnum=c.conkey[1]))
		   JOIN pg_catalog.pg_namespace AS ns1 ON r1.relnamespace=ns1.oid
		   LEFT JOIN (
		   pg_catalog.pg_class AS r2 JOIN pg_catalog.pg_namespace AS ns2 ON (r2.relnamespace=ns2.oid)
		   ) ON (c.confrelid=r2.oid)
		   LEFT JOIN pg_catalog.pg_attribute AS f2 ON
		   (f2.attrelid=r2.oid AND ((c.confkey[1]=f2.attnum AND c.conkey[1]=f1.attnum)))
		 	WHERE ";
					
		foreach($tables as $table => $keys) {
			list($schema, $tbl) = $this->splitObjectName( $table );
			$schema = trim($schema, '"\' ');
			if ($schema)
				$sql .= "(ns1.nspname='".$schema."' and r1.relname='".trim($tbl, '"\' ')."') OR ";
			else
				$sql .= "(r1.relname='".trim($tbl, '"\' ')."') OR ";
		}
		$sql = substr($sql, 0, -3);
		$res = pg_query($this->conn, $sql);
		if (!$res)
			return;
		while($row = pg_fetch_array($res)) {
			for($i=0; $i<count($fields); $i++) {
				if($fields[$i]->table == $row[2] && $fields[$i]->name == $row[3]) {
					$fields[$i]->pkey = $row[0] == "p" ? 1 : 0;
					$fields[$i]->ukey = $row[0] == "u" ? 1 : 0;
					break;
				}
			}
		}
	}

	protected function getCreateCommandForView( $name )
	{
		list($schema, $name) = $this->splitObjectName( $name );
		$sql = "SELECT c.relname, n.nspname, pg_catalog.pg_get_userbyid(c.relowner) AS relowner,
			pg_catalog.pg_get_viewdef(c.oid, true) AS createcmd,
			pg_catalog.obj_description(c.oid, 'pg_class') AS comments
			FROM pg_catalog.pg_class c
			LEFT JOIN pg_catalog.pg_namespace n ON (n.oid = c.relnamespace)
			WHERE (c.relname = '" . $this->escape($name) . "') AND n.nspname='" . $this->escape( $schema ) . "'";
		
		if (!$this->query($sql, '_temp') || $this->numRows('_temp') == 0)
			return '';
		
		$row = $this->fetchRow('_temp');
		$cmd = 'CREATE VIEW ' . $this->quote( $row['nspname'] . '.' . $row['relname'] ) . " AS \n"
				. $row['createcmd'];
		
		if ($row['comments'] != '') {
			$cmd = "BEGIN; \n" . $cmd . "\nCOMMENT ON VIEW " . $this->quote( $row['nspname'] . '.' . $row['relname'] ) . " IS '" . $this->escape($row['comments']) . "';\nEND;";
		}
		
		return $cmd;
	}
	
}
?>