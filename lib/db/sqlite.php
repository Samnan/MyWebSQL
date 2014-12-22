<?php
/**
 * This file is a part of MyWebSQL package
 * A simple and easy to debug sqlite wrapper class
 *
 * @file:      lib/db/sqlite.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (defined("CLASS_DB_SQLITE_INCLUDED"))
	return true;

define("CLASS_DB_SQLITE_INCLUDED", "1");

define('SQLITE_FILEEXT', 'db|db3|sqlite|sqlite3');

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

class DB_Sqlite {
	var $ip, $user, $password, $db;
	var $conn;
	var $result;		// array
	var $errMsg;
	var $escapeData;
	var $lastQuery;
	var $queryTime;
	var $authOptions;   // used for additional login security

	var $pragmas = array('auto_vacuum','automatic_index','checkpoint_fullfsync',
		'foreign_keys','fullfsync','ignore_check_constraints','journal_mode',
		'journal_size_limit','locking_mode','max_page_count','page_size','recursive_triggers',
		'secure_delete','synchronous','temp_store','user_version','wal_autocheckpoint'
	);

	function DB_Sqlite() {
		$this->conn = null;
		$this->errMsg = null;
		$this->escapeData = true;
		$this->result = array();

		$this->authOptions = array();
	}

	function name() {
		return 'sqlite';
	}

	function hasServer() {
		return false;
	}

	function hasObject($type) {
		switch($type) {
			case 'table':
			case 'view':
			case 'trigger':
				return true;
				break;
		}
		return false;
	}

	function getObjectTypes() {
		$types = array(
			'tables', 'views', 'triggers'
		);

		return $types;
	}

	function getObjectList( $details = false ) {
		$data = array(
			'tables' => $this->getTables( $details ),
			'views' => $this->getViews(),
			'triggers' => $this->getTriggers()
		);

		return $data;
	}

	function getBackQuotes() {
		return '';
	}

	function getQuotes() {
		return '"';
	}

	function getStandardDbList() {
		return array( 'SQLITE_MASTER' );
	}


	function setAuthOptions($options) {
		$this->authOptions = $options;
	}

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

		if (!function_exists('sqlite_open')) {
			return $this->error(str_replace('{{NAME}}', 'SQLite', __('{{NAME}} client library is not installed')));
		}

		if ($db && !($this->conn = sqlite_open($ip . $db, 0666)) )
			return $this->error(sqlite_error_string());

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
		@sqlite_close($this->conn);
		$this->conn = false;
		return true;
	}

	function getCurrentUser() {
		return $this->user;
	}

	function selectDb($db) {
		$this->db = $db;
		if ( ! ($this->conn = sqlite_open($this->ip . $db, 0666)) )
			return $this->error(sqlite_error_string(sqlite_last_error()));
		$this->selectVersion();
		return true;
	}

	function createDatabase( $name ) {
		if ( empty($name) || is_file($this->ip.$name) ) {
            return false;
		}

		// concat .db at the end of name if not already given
		if ( !preg_match('/.db$/', $name) )
			$name .= '.db';
        $result = touch( $this->ip.$name );
		if ($result) {
        	chmod( $this->ip.$name, 0666 );
			return true;
		}
		return false;
	}

	function query($sql, $stack=0) {		// call with query($sql, 1) to store multiple results
		if (!$this->conn) {
			log_message("DB: Connection has been closed");
			return false;
		}

		$this->result[$stack] = "";

		$this->lastQuery = $sql;
		$this->queryTime = $this->getMicroTime();
		$this->result[$stack] = @sqlite_query($sql, $this->conn);
		$this->queryTime = $this->getMicroTime() - $this->queryTime;

		if (!$this->result[$stack]) {
			$this->errMsg = sqlite_error_string(sqlite_last_error($this->conn));
			log_message("DB: $sql ::: ".$this->errMsg);
			return false;
		}

		return true;
	}

	function getWarnings() {
		// @@TODO: find a solution for this
		$ret = array();
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
		return sqlite_last_insert_rowid($this->conn);
	}

	function getResult($stack=0) {
		return $this->result[$stack];
	}

	function hasResult($stack=0) {
		return ($this->result[$stack] !== TRUE && $this->result[$stack] !== FALSE);
	}

	function fetchRow($stack=0, $type="") {
		if($type == "")
			$type = SQLITE_BOTH;
		else if ($type == "num")
			$type = SQLITE_NUM;
		else if ($type == "assoc")
			$type = SQLITE_ASSOC;

		if (!$this->result[$stack]) {
			log_message("DB: called fetchRow[$stack] but result is false");
			return;
		}
		return @sqlite_fetch_array($this->result[$stack], $type);
	}

	function fetchSpecificRow($num, $type="", $stack=0) {
		if($type == "")
			$type = SQLITE_BOTH;
		else if ($type == "num")
			$type = SQLITE_NUM;
		else if ($type == "assoc")
			$type = SQLITE_ASSOC;

		if (!$this->result[$stack]) {
			log_message("DB: called fetchSpecificRow[$stack] but result is false");
			return;
		}

		sqlite_seek($this->result[$stack], $num);
		return @sqlite_fetch_array($this->result[$stack], $type);
	}

	function numRows($stack=0) {
		return sqlite_num_rows($this->result[$stack]);
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
		return sqlite_escape_string($str);
	}

	function quote($str) {
		if(strpos($str, '.') === false)
			return '[' . $str . ']';
		return '[' . str_replace('.', '].[', $str) . ']';
	}

	function setEscape($escape=true) {
		$this->escapeData = $escape;
	}

	function getAffectedRows() {
		return sqlite_changes($this->conn);
	}

	/**************************************/
	function getDatabases() {
		$ret = array();
		$d = opendir($this->ip);
        while(($entry = readdir($d)) != false) {
            if ( $entry!="." && $entry!=".." && is_file($this->ip.$entry) &&
            		preg_match('/^.*\.('.SQLITE_FILEEXT.')$/i', $entry) ) {
				$ret[] = $entry;
            }
        }
		closedir($d);
		return $ret;
	}

	function getTables( $details = false ) {
		if (!$this->db)
			return array();
		$this->query("select name from SQLITE_MASTER where type = 'table' order by 1");
		$ret = array();
		while($row = $this->fetchRow())
			$ret[] = $details ?	array(
				$row[0], // table name
				0, // number of records,
				0, // size of the table
				'', // last update timestamp
			) : $row[0];
		return $ret;
	}

	function getViews() {
		if (!$this->db)
			return array();
		$this->query("select name from SQLITE_MASTER where type = 'view' order by 1");
		$ret = array();
		while($row = $this->fetchRow())
			$ret[] = $row[0];
		return $ret;
	}

	function getProcedures() {
		return array();
	}

	function getFunctions() {
		return array();
	}

	function getTriggers() {
		if (!$this->db)
			return array();
		$this->query("select name from SQLITE_MASTER where type = 'trigger' order by 1");
		$ret = array();
		while($row = $this->fetchRow())
			$ret[] = $row[0];
		return $ret;
	}

	function getEvents() {
		return array();
	}

	/**************************************/
	function getFieldInfo($stack=0) {
		$fields = array();
		$i = 0;
		if ( ( $table = Session::get('select', 'table') ) != '' ) {
			// query from a table, so we can find keys related information using pragma
			$this->result['_tinfo'] = @sqlite_query('PRAGMA table_info(' . $this->quote($table) . ')', $this->conn);
			while ($row = $this->fetchRow('_tinfo')) {
			$f = new StdClass;
				$f->name = $row['name'];
				$f->table = $table;
				$f->not_null = $row['notnull'];
				$f->blob = $row['type'] == 'BLOB' ? 1 : 0;
				$f->pkey = $row['pk'];
				$f->ukey = 0;
				$f->mkey = 0;
				$f->zerofill = 0;
				$f->unsigned = 0;
				$f->autoinc = 0;
				$f->numeric = $row['type'] == 'INTEGER' ? 1 : 0;
				$f->type = $row['type'] == 'INTEGER' ? 'numeric' : ( $row['type'] == 'BLOB' ? 'binary' : 'text' );
				$fields[] = $f;
				$i++;
			}
		} else {
			while ($i < sqlite_num_fields($this->result[$stack])) {
				$f = new StdClass;
				$f->name = sqlite_field_name($this->result[$stack], $i);
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
		}
		return $fields;
	}

	function getMicroTime() {
		list($usec, $sec) = explode(" ",microtime());
		return ((float)$usec + (float)$sec);
	}

	function selectVersion() {
		Session::set('db', 'version', 0);
		Session::set('db', 'version_full', 'SQLite');
		Session::set('db', 'version_comment', '');
	}

	function getCreateCommand($type, $name) {
		$cmd = '';
		$type = $this->escape($type);
		$name = $this->escape($name);

		$sql = "select sql from SQLITE_MASTER where type = '$type' and name = '".$name."'";
		if (!$this->query($sql) || $this->numRows() == 0)
			return '';

		$row = $this->fetchRow();
		$cmd = $row[0];

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
		$res = $this->query($sql, '_temp');
		if ($this->numRows() == 0)
			return ( (object) array('list' => array()) );
		$row = $this->fetchRow('_temp');
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
		$arr = array();
		return $arr;
	}

	function getCharsets() {
		$arr = array();
		return $arr;
	}

	function getCollations() {
		$arr = array();
		return $arr;
	}

	function getTableFields($table) {
		// @@TODO: fix this
		$fields = array();
		return $fields;
	}

	function getTableProperties($table) {
		$sql = "show table status where `Name` like '".$this->escape($table)."'";
		if (!$this->query($sql, "_tmp_query"))
			return FALSE;
		return $this->fetchRow("_tmp_query");
	}

	function queryTableStatus() {
		$sql = 'select * from SQLITE_MASTER';
		return $this->query($sql);
	}

	function getTableDescription( $table ) {
		$sql = "describe " . $this->quote( $table );
		return $this->query($sql);
	}

	function flush($option = '', $skiplog=false) {
		return true;
	}

	function getLastQuery() {
		return $this->lastQuery;
	}


	function getInsertStatement($tbl) {
		$sql = "select sql from SQLITE_MASTER where type = 'table' and name = '".$this->escape($tbl)."'";
		if (!$this->query($sql, '_insert'))
			return false;

		$row = $this->fetchRow('_insert');
		$table_info = $this->parseCreateStatement($row[0]);
		$fields = $table_info[0];
		$str = "INSERT INTO ".$tbl." (";
		$str .= $fields[0];

		$str2 = '';

		//if ($row["Extra"] == "auto_increment")
		//	$str2 = " values (NULL";
		//else
			$str2 = " VALUES (\"\"";

		for($i=1; $i<count($fields); $i++) {
			$str .= "," . $fields[$i];
			//if ($row["Extra"] == "auto_increment")
			//	$str2 .= ",NULL";
			//else
				$str2 .= ",\"\"";
		}

		$str .= ")";
		$str2 .= ")";

		return $str.$str2;
	}

	function getUpdateStatement($tbl) {
		$sql = "select sql from SQLITE_MASTER where type = 'table' and name = '".$this->escape($tbl)."'";
		if (!$this->query($sql, '_update'))
			return false;

		$row = $this->fetchRow('_update');
		$table_info = $this->parseCreateStatement($row[0]);
		$fields = $table_info[0];
		$pKey = $table_info[1];

		$str = "UPDATE ".$tbl." SET ";
		$str .= $fields[0] . "=\"\"";

		$str2 = '';

		for($i=1; $i<count($fields); $i++) {
			$str .= "," . $fields[$i] . "=\"\"";
			if ($pKey == "") {
				if ($str2 != "")
					$str2 .= " AND ";
				$str2 .= "$fields[$i]=\"\"";
			}
		}

		// if we found a primary key, then use it only for where clause and discard other fields
		if ($pKey != '')
			$str2 = "$pKey=\"\"";
		if ($str2 != "")
			$str2 = " WHERE " . $str2;

		return $str . $str2;
	}

	// @@TODO: use vacumm command and test here
	function truncateTable($tbl) {
		return $this->query('DELETE FROM '.$this->quote($tbl));
	}

	function renameObject($name, $type, $new_name) {
		$result = false;
		if($type == 'table') {
			$query = 'ALTER TABLE '.$this->escape($name).' RENAME TO '.$this->escape($new_name);
			$result = $this->query($query);
		} else {
			//@@TODO: fix logic according to sqlite
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
		$query = 'DROP '.$this->escape($type).' '.$this->escape($name);
		$result = $this->query($query);
		return $result;
	}

	function copyObject($name, $type, $new_name) {
		$result = false;
		if($type == 'table') {
			$query = 'CREATE '.$this->escape($type).' ' . $this->escape($new_name) . ' AS SELECT * FROM ' . $this->escape($name);
			$result = $this->query($query);
		} else {
			//@@TODO: fix logic according to sqlite
			$command = $this->getCreateCommand($type, $name);
			$search = '/(create.*'.$type. ' )('.$name.'|\`'.$name.'\`)/i';
			$replace = '${1} `'.$new_name.'`';
			$query = preg_replace($search, $replace, $command, 1);
			$result = $this->query($query);
		}
		return $result;
	}

	function getAutoIncField($table) {
		$sql = "show full fields from [".$this->escape($table)."]";
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
					$str .= "\n/* Export data */\n";
				} else if ( $obj == 'table' ) {
					$str = "/* Table data export for table ".$name." */\n";
					$str .= "\n/* Export data */\n";
				} else if ( $obj == 'query' ) {
					$str = "/* Export results for query data */\n";
					$str .= "/* Query: \n".$name."\n*/\n";
					$str .= "\n/* Export data */\n";
				}
			break;
		}
		return $str;
	}

	function addExportFooter( $type='insert' ) {
		return "";
	}

	function set_constraint( $constraint, $value ) {
		switch ($constraint) {
			case 'fkey':
				//$this->query('SET FOREIGN_KEY_CHECKS=' . ($value ? '1' : '0') );
			break;
		}
	}

	/***** object specific functions ******/
	protected function parseCreateStatement($str) {
		$extra = strtok( $str, "(" );
		$primary = '';
		while( $fieldnames[] = strtok(",") ) {};
		array_pop( $fieldnames );
		foreach( $fieldnames as $no => $field ) {
			if ( strpos($field, "PRIMARY KEY") && $no > 0 ) {
				strtok( $field, "(" );
				$primary = trim(strtok( ")" ));
				unset($fieldnames[$no]);
			} else
				$fieldnames[$no] = trim(strtok( $field, " " ));
		}
		return array($fieldnames, $primary);
	}
}
?>