<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      lib/tableeditor.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

class tableEditor {
	var $db;
	var $table;
	var $fields;
	var $deleteFields;
	var $indexes;
	var $properties;
	var $_loaded;
	var $_modified;
	
	var $backquote;
	
	var $_sql; // last generated sql is saved for debugging purposes

	function __construct(&$db) {
		$this->db = $db;
		$this->_loaded = false;
		$this->_modified = false;
		$this->fields = false;
		$this->deleteFields = false;
		$this->indexes = false;
		$this->properties = false;
		
		$this->backquote = $db->getBackQuotes();
	}

	function loadTable($fields=true, $indexes=true, $props=true) {
		if($fields == true) {
			$sql = "show fields from ".$this->backquote.$this->db->escape($this->table).$this->backquote;
			if (!$this->db->query($sql, "create_stmt"))
				return FALSE;
			
			$this->fields = array();
			while($row = $this->db->fetchRow("create_stmt"))
				$this->fields[] = $this->fetchFieldInfo($row);
		}

		if ($indexes == true) {
			if (!$this->fetchIndexInfo())
				return FALSE;
		}
		
		if ($props == true) {
			$row = $this->db->getTableProperties($this->table);
			$this->properties = $this->fetchTableProperties($row);
		}
		
		$this->_loaded = true;
		return TRUE;
	}

	function setName($name) {
		$this->table = $name;
		$this->_modified = true;
	}

	function setFields($fields) {
		$this->fields = $fields;
		$this->_modified = true;
	}
	
	function setIndexes($indexes) {
		$this->indexes = $indexes;
		$this->_modified = true;
	}

	function setProperties($properties) {
		$this->properties = $properties;
		$this->_modified = true;
	}

	function deleteFields($fields) {
		$this->deleteFields = $fields;
		$this->_modified = true;
	}
	
	function getName() {
		return $this->table;
	}

	function getFields() {
		return $this->fields;
	}
	
	function getIndexes() {
		return $this->indexes;
	}

	function getProperties() {
		return $this->properties;
	}

	function getSql() {
		return $this->_sql;
	}

	function getCreateStatement() {
		if(!$this->_modified)
			return $this->_sql;
			
		$str = 'CREATE TABLE '.$this->backquote.$this->db->escape($this->table).$this->backquote." (\n";
		$list = $this->fields;
	
		for($i=0; $i<count($list); $i++) {
			$one = $list[$i];
			
			$str .= $this->getFieldDefinition($one) . ",\n";
		}
		
		$key = $this->generateKeyStatement();
		$str = $key == '' ? substr($str, 0, -2) : $str . $key;
		
		$str .= "\n)\n";
		
		$str .= $this->generatePropsStatement();
		
		$this->_sql = $str;
		
		return $str;
	}
	
	function getAlterStatement() {
		if(!$this->_modified)
			return $this->_sql;
		
		$str = '';
		
		if ($this->deleteFields) {
			$list = $this->deleteFields;
		
			for($i=0; $i<count($list); $i++) {
				$fname = $list[$i];
				$str .= 'DROP COLUMN ' . $this->backquote . $fname . $this->backquote . ",\n";
			}
		}
		
		if ($this->fields) {
			$list = $this->fields;
			for($i=0; $i<count($list); $i++) {
				$one = $list[$i];
				$state = $one->fstate;
				if ($state == 'new') {
					$str .= 'ADD COLUMN ' . $this->getFieldDefinition($one);
					if ($i == 0)
						$str .= ' FIRST';
					else {
						$prev_field = $list[$i-1];
						$str .= ' AFTER ' . $this->backquote . $prev_field->fname . $this->backquote;
					}
					$str .= ",\n";
				}
			}
			
			for($i=0; $i<count($list); $i++) {
				$one = $list[$i];
				$state = $one->fstate;
				if ($state == 'change')
					$str .= 'CHANGE ' . $this->backquote . $one->oname . $this->backquote . ' ' . $this->getFieldDefinition($one) . ",\n";
			}
		}

		if ($this->fields || $this->indexes)
			$str .= $this->generatePrimaryKeyStatement();
		
		if ($this->properties) {
			$str2 = $this->generatePropsStatement();
			if ($str2 != '')
				$str .= $str2;
			else
				$str = substr($str, 0, -2);
		}
		
		if ($str != '')
			$str = 'ALTER TABLE '.$this->backquote.$this->db->escape($this->table).$this->backquote."\n" . $str;
		
		$this->_sql = $str;
		
		return $str;
	}
	
	/* used in create command */
	function generateKeyStatement() {
		$str = '';
		$numKeys = 0;
		$list = $this->fields;
	
		for($i=0; $i<count($list); $i++) {
			$one = $list[$i];
			if ($one->fpkey) {
				$str .= $this->backquote . $this->db->escape($one->fname) . $this->backquote . ',';
				$numKeys++;
			}
		}
		
		if ($numKeys > 0) {
			$str = 'PRIMARY KEY (' . substr($str, 0, -1) . ')';
		}
		return $str;
	}
	
	/* used in alter command */
	function generatePrimaryKeyStatement() {
		$this->fetchIndexInfo();
		$pkey = isset($this->indexes['PRIMARY']) ? $this->indexes['PRIMARY'] : array();
		
		$str = '';
		
		$old = array();
		for($i=0; $i<count($pkey); $i++)
			$old[] = $pkey[$i]->column;
		
		$new = array();
		for($j=0; $j<count($this->fields); $j++) {
			$one = $this->fields[$j];
			if ($one->fpkey == 'Yes')
				$new[] = $one->oname;
		}
		
		if ( count($old) > 0 && !$this->isIndexMatch($old, $new) )
			$str .= " DROP PRIMARY KEY,\n";
		
		if ( count($new) > 0 && !$this->isIndexMatch($old, $new) ) {
			$str2 = '';
			for($i=0; $i<count($this->fields); $i++) {
				$one = $this->fields[$i];
				if ($one->fpkey == 'Yes')
					$str2 .= $this->backquote.$one->fname.$this->backquote.',';
			}
			$str .= " ADD PRIMARY KEY(".substr($str2, 0, -1)."),\n";
		}
		
		return $str;
	}
	
	/* used in create, alter command */
	function generatePropsStatement() {
		if(!$this->_modified)
			return '';
		$str = '';
		
		$row = $this->db->getTableProperties($this->table);
		$oldProps = $this->fetchTableProperties($row);		
		
		if(v($this->properties->engine) && v($this->properties->engine) != v($oldProps->engine))
			$str .= ' ENGINE='.$this->db->escape($this->properties->engine);
		
		if(v($this->properties->charset) && v($this->properties->charset) != v($oldProps->charset))
			$str .= ' CHARSET='.$this->db->escape($this->properties->charset);
			
		if(v($this->properties->collation) && v($this->properties->collation) != v($oldProps->collation))
			$str .= ' COLLATE='.$this->db->escape($this->properties->collation);
			
		if(v($this->properties->comment) && v($this->properties->comment) != v($oldProps->comment))
			$str .= " COMMENT='".$this->db->escape($this->properties->comment) . "'";
		
		return $str;
	}

	function fetchFieldInfo($row) {
		$field = new StdClass();
		$field->fname = $row['Field'];
		
		$len = strpos($row['Type'], '(');
		if ($len === FALSE) { // normal field type without any length
			$field->ftype = strtolower($row['Type']);
			$field->flen = '';
			$field->flist = '';
		}
		else {
			$field->ftype = strtolower(substr($row['Type'], 0, $len));
			if ($field->ftype == 'enum' || $field->ftype == 'set') { // enum or set type with list of values
				$field->flen = '';
				$regex = "/'(.*?)'/";
				preg_match_all($regex, $row['Type'], $list);
				$list = $list[1];
				$field->flist = $list;
			}
			else { // field type with valid length identifier
				$len2 = strpos($row['Type'], ')', $len);
				$field->flen = ($len2 === FALSE ) ? '' : substr($row['Type'], $len+1, $len2-$len-1);
				$field->flist = '';
			}
		}
		
		$field->fval = $row['Default'];
		//@todo: fix the following with proper regex search
		$field->fsign = strpos($row['Type'], ' unsigned') === FALSE ? '0' : '1';
		$field->fzero = strpos($row['Type'], ' zerofill') === FALSE ? '0' : '1';
		$field->fpkey = $row['Key'] == 'PRI' ? '1' : '0';
		$field->fauto = strpos($row['Extra'], 'auto_increment') === FALSE ? '0' : '1';
		$field->fnull = $row['Null'] == 'NO' ? '1' : '0';
		
		return $field;
	}
	
	function fetchIndexInfo($return = false) {
		$indexes = array();

		$sql = "show indexes from ".$this->backquote.$this->db->escape($this->table).$this->backquote;
		if (!$this->db->query($sql, "index_stmt"))
			return FALSE;
		while($row = $this->db->fetchRow("index_stmt")) {
			$key_name = $row['Key_name'];
			$index = new StdClass();
			$index->column = $row['Column_name'];
			$index->type = $row['Index_type'];
			$index->unique = $row['Non_unique'] == '1' ? '0' : '1';
			$index->order = $row['Seq_in_index'];
			
			if (!isset($indexes[$key_name]))
				$indexes[$key_name] = array();
			
			$indexes[$key_name][] = $index;
		}
		
		if ($return)
			return $indexes;

		$this->indexes = $indexes;
		return TRUE;
	}
	
	function fetchTableProperties($row) {
		$props = new StdClass();
		$props->engine = $row['Engine'];
		$props->charset = '';
		$props->collation = $row['Collation'];
		$props->comment = $row['Comment'];
		
		// find out the charset is a bit trickier than you think...
		$sql = "show collation like '".$this->db->escape($props->collation)."'";
		if (!$this->db->query($sql, "temp_stmt") || $this->db->numRows("temp_stmt") == 0)
			return $props;
   	
		$row = $this->db->fetchRow("temp_stmt");
		$props->charset = $row['Charset'];
		
		return $props;
	}
	
	function getFieldDefinition($one) {
		$str = $this->backquote.$one->fname.$this->backquote.' '.$one->ftype;
			
		if (v($one->flist) && count($one->flist) > 0) {	// enum/set values
			$str2 = '(';
			for($j=0; $j<count($one->flist);$j++)
				$str2 .= "'" . str_replace("'", "\'", $one->flist[$j]) . "',";
			$str .= substr($str2, 0, -1) . ')';
			unset($str2);
		}
		else if (v($one->flen))
			$str .= '(' . $one->flen . ')';
		
		if ($one->fsign)
			$str .= ' UNSIGNED';
		
		if ($one->fzero)
			$str .= ' ZEROFILL';
		
		if ( $one->fval != '' ) {
			$str .= ' DEFAULT ' . $this->getDefaultValueText($one->fval);
		}
		
		if ( $one->fnull )
			$str .= ' NOT NULL';
		
		if ($one->fauto)
			$str .= ' AUTO_INCREMENT';
		
		return $str;
	}
	
	function getDefaultValueText($val) {
		if ($val == 'NULL')
			return 'NULL';

		return $val;
	}
	
	function getAlterIndexStatement() {
		if(!$this->_modified)
			return $this->_sql;
		
		$oldList = $this->fetchIndexInfo(true);
		$list = $this->indexes;
		
		$str = 'ALTER TABLE '.$this->backquote.$this->db->escape($this->table).$this->backquote."\n";
		
		// delete indexes no longer required
		foreach($oldList as $key => $info) {
			if(array_key_exists($key, $list) === FALSE) {
				if ($key == 'PRIMARY')
						$str .= 'DROP PRIMARY KEY'. ",\n";
				else
					$str .= 'DROP KEY ' . $this->backquote . $key . $this->backquote . ",\n";
			}
		}
		
		// add/modify remaining indexes
		foreach($list as $key => $info) {
			$match = false;
			if(array_key_exists($key, $oldList)) {
				$match = $this->matchIndexes($info, $oldList[$key]);
				// if indexes are different, only then delete and recreate
				if ( !$match ) {
					if ($key == 'PRIMARY')
						$str .= 'DROP PRIMARY KEY'. ",\n";
					else
						$str .= 'DROP KEY ' . $this->backquote . $key . $this->backquote . ",\n";
				}
			}
			
			// add index only if added/changed
			if ( !$match ) {
				$str .= 'ADD ';
				if (v($info[0]->unique) && $key != 'PRIMARY')
					$str .= 'UNIQUE ';
				if (v($info[0]->type) == 'FULLTEXT')
					$str .= 'FULLTEXT ';
				
				if ($key == 'PRIMARY')
					$str .= 'PRIMARY KEY ';
				else
					$str .= 'KEY ' . $this->backquote . $this->db->escape($key) . $this->backquote;
				
				$str .= '(';
				foreach($info as $field) {
					if (v($field->length) != '')
						$str .= $this->backquote . $this->db->escape($field->column) . $this->backquote . '(' . $field->length . '),';
					else
						$str .= $this->backquote . $this->db->escape($field->column) . $this->backquote . ',';
				}
				
				$str = substr($str, 0, -1);
				$str .= ')' . ",\n";
			}
		}
		
		$str = substr($str, 0, -2);
		$this->_sql = $str;
		
		return $str;
	}
	
	function matchIndexes($new, $old) {
		if (count($new) != count($old))
			return false;
			
		foreach($new as $key => $info) {
			if (!isset($old[$key]) || $info != $old[$key])
				return false;
		}
		
		foreach($old as $key => $info) {
			if (!isset($new[$key]) || $info != $new[$key])
				return false;
		}
		
		return true;
	}
	
	function isIndexMatch($old, $new) {
		sort($old);
		sort($new);
		
		return ($old === $new);
	}
}
?>