<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      lib/privileges.php
 * @author     Ovais Tariq <http://ovaistariq.net>
 * @maintainer Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

class Privileges
{
	public static $privilegesTable = '`mysql`.`user`';
	protected static $allPrivileges = array(
		'Alter_priv'             => 'Alter',
		'Alter_routine_priv'     => 'Alter routine',
		'Create_priv'            => 'Create',
		'Create_routine_priv'    => 'Create routine',
		'Create_tmp_table_priv'  => 'Create temporary tables',
		'Create_view_priv'       => 'Create view',
		'Create_tablespace_priv' => 'Create tablespace',
		'Create_user_priv'       => 'Create user',
		'Delete_priv'            => 'Delete',
		'Drop_priv'              => 'Drop',
		'Event_priv'             => 'Event',
		'Execute_priv'           => 'Execute',
		'File_priv'              => 'File',
		'Grant_priv'             => 'Grant option',
		'Index_priv'             => 'Index',
		'Insert_priv'            => 'Insert',
		'Lock_tables_priv'       => 'Lock tables',
		'Process_priv'           => 'Process',
		'References_priv'        => 'References',
		'Reload_priv'            => 'Reload',
		'Repl_client_priv'       => 'Replication client',
		'Repl_slave_priv'        => 'Replication slave',
		'Select_priv'            => 'Select',
		'Show_db_priv'           => 'Show databases',
		'Show_view_priv'         => 'Show view',
		'Shutdown_priv'          => 'Shutdown',
		'Super_priv'             => 'Super',
		'Trigger_priv'           => 'Trigger',
		'Update_priv'            => 'Update'
	);
	protected static $dbManager;

	public static function setDb($dbManager) {
		self::$dbManager = $dbManager;
	}

	public static function getNames() {
		static $supportedPrivileges = false;

		if( false == $supportedPrivileges ) {
			$sql = "SHOW COLUMNS FROM " . self::$privilegesTable;
			if( self::$dbManager->query( $sql ) ) {
				$supportedPrivileges = array();

				while( $row = self::$dbManager->fetchRow() ) {
					if( strstr( $row['Field'], 'priv' ) )
						$supportedPrivileges[$row['Field']] = true;
				}

				$supportedPrivileges = array_intersect_key( self::$allPrivileges, $supportedPrivileges );
			}
		}

		return $supportedPrivileges;
	}

	public $username;
	public $host;

	protected $privileges;

	public function  __construct($username, $host) {
		$this->username = trim( $username );
		$this->host     = trim( $host );

		$this->privileges = false;
	}

	public function get() {
		if( false == $this->privileges ) {
			$tableName = self::$privilegesTable;

			$sql = "SELECT * FROM $tableName WHERE `User` = '{$this->username}' AND `Host` = '{$this->host}'";
			if( false == self::$dbManager->query( $sql ) )
				return false;

			$privileges = self::$dbManager->fetchRow();

			$this->privileges = array();
			foreach( (array)$privileges as $privName => $privValue ) {
				if( strstr( $privName, 'priv' ) && $privValue == 'Y' )
					$this->privileges[] = $privName;
			}
		}

		return $this->privileges;
	}

	public function set($privileges) {
		$selectedPriv  = array();
		$newPrivileges = array();

		foreach( (array)self::getNames() as $privName => $privDesc ) {
			if( in_array( $privName, $privileges ) ) {
				$selectedPriv[]   = "$privName = 'Y'";
				$newPrivileges[] = $privName;
			}
			else {
				$selectedPriv[] = "$privName = 'N'";
			}
		}
		$selectedPriv = implode( ', ', $selectedPriv );

		$tableName = self::$privilegesTable;

		$sql = "UPDATE $tableName SET $selectedPriv WHERE `User` = '{$this->username}' AND `Host` = '{$this->host}'";
		if( false == self::$dbManager->query( $sql ) )
			return false;

		$this->privileges = $newPrivileges;

		return true;
	}
}

class DbPrivileges extends Privileges
{
	public static $privilegesTable = '`mysql`.`db`';

	public static function getNames() {
		static $supportedPrivileges = false;

		if( false == $supportedPrivileges ) {
			$sql = "SHOW COLUMNS FROM " . self::$privilegesTable;
			if( self::$dbManager->query( $sql ) ) {
				$supportedPrivileges = array();

				while( $row = self::$dbManager->fetchRow() ) {
					if( strstr( $row['Field'], 'priv' ) )
						$supportedPrivileges[$row['Field']] = true;
				}

				$supportedPrivileges = array_intersect_key( self::$allPrivileges, $supportedPrivileges );
			}
		}

		return $supportedPrivileges;
	}

	public $dbName;

	public function __construct($username, $host, $dbName) {
		$this->dbName = trim( $dbName );

		parent::__construct( $username, $host );
	}

	public function get() {
		if( false == $this->privileges ) {
			$tableName = self::$privilegesTable;

			$sql = "SELECT * FROM $tableName WHERE `User` = '{$this->username}' AND `Host` = '{$this->host}' AND `Db` = '{$this->dbName}'";
			if( false == self::$dbManager->query( $sql ) )
				return false;

			$privileges = self::$dbManager->fetchRow();

			$this->privileges = array();
			foreach( (array)$privileges as $privName => $privValue ) {
				if( strstr( $privName, 'priv' ) && $privValue == 'Y' )
					$this->privileges[] = $privName;
			}
		}

		return $this->privileges;
	}

	public function set($privileges) {
		$selectedPriv   = array();
		$newPrivileges  = array();
		$currPrivileges = $this->get();

		foreach( (array)self::getNames() as $privName => $privDesc ) {
			if( in_array( $privName, $privileges ) ) {
				$selectedPriv[]   = "$privName = 'Y'";
				$newPrivileges[] = $privName;
			}
			else {
				$selectedPriv[] = "$privName = 'N'";
			}
		}
		$selectedPriv = implode( ', ', $selectedPriv );

		$tableName = self::$privilegesTable;

		if( count( $newPrivileges ) < 1 && count( $currPrivileges ) > 0 )
			$sql = "DELETE FROM $tableName WHERE `User` = '{$this->username}' AND `Host` = '{$this->host}' AND `Db` = '{$this->dbName}'";
		elseif( count( $newPrivileges ) > 0 )
			$sql = "REPLACE INTO $tableName SET $selectedPriv, `User` = '{$this->username}', `Host` = '{$this->host}', `Db` = '{$this->dbName}'";
		else
			$sql = '';

		if( $sql && false == self::$dbManager->query( $sql ) )
			return false;

		$this->privileges = $newPrivileges;

		return true;
	}
}