<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/views/pgsql/templates/datatypes.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	// @@TODO: add support for lots of other pgsql data types
	$dataTypes = array(
		'bigint' => array('type' => 'numeric'),
		'bigserial' => array('type' => 'numeric'),
		'bit' => array('type' => 'special'),
		'bytea' => array('type' => 'binary'),
		'boolean' => array('type' => 'special'),
		'character' => array('type' => 'char'),
		'character varying' => array('type' => 'char'),
		'date' => array('type' => 'date'),
		'datetime' => array('type' => 'date'),
		'decimal' => array('type' => 'numeric'),
		'double' => array('type' => 'numeric'),
		'enum' => array('type' => 'char'),
		'float' => array('type' => 'numeric'),
		'integer' => array('type' => 'numeric'),
		'interval' => array('type' => 'date'),
		'longblob'=> array('type' => 'binary'),
		'longtext'=> array('type' => 'text'),
		'mediumblob'=> array('type' => 'binary'),
		'mediumint' => array('type' => 'numeric'),
		'mediumtext'=> array('type' => 'text'),
		'numeric' => array('type' => 'numeric'),
		'real' => array('type' => 'numeric'),
		'serial' => array('type' => 'numeric'),
		'set'=> array('type' => 'char'),
		'smallint' => array('type' => 'numeric'),
		'text'=> array('type' => 'text'),
		'time' => array('type' => 'date'),
		'timestamp' => array('type' => 'date'),
		'tinyblob'=> array('type' => 'binary'),
		'tinyint' => array('type' => 'numeric'),
		'tinytext'=> array('type' => 'text'),
		'uuid' => array('type' => 'special'),
		'varbinary'=> array('type' => 'binary'),
		'varchar'=> array('type' => 'char'),
		'year' => array('type' => 'date'),
	);

?>