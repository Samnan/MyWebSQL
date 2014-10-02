<?php
/**
 * This file is a part of MyWebSQL package
 * user classes used by user manager
 *
 * @file:      lib/user.php
 * @author     Ovais Tariq <http://ovaistariq.net>
 * @maintainer Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

abstract class User {
	protected static $dbManager;

	public $userName;
	public $host;
	public $password;

	protected $globalPrivileges;
	protected $dbPrivileges;

	public static function setDb($dbManager) {
		self::$dbManager = $dbManager;

		Privileges::setDb( $dbManager );
	}

	public static function factory($legacy = false) {
		if( $legacy )
			return new User_4x();

		return new User_5x();
	}

	public function __construct() {
		$this->userName  = false;
		$this->host      = false;
		$this->password  = false;

		$this->globalPrivileges = false;
		$this->dbPrivileges     = array();
	}

	public abstract function add();

	public abstract function update($newUsername, $newHost = '%');

	public function updatePassword($newPassword = '') {
		if( false == $this->userName || false == $this->host )
			return false;

		$userName = self::$dbManager->escape( $this->userName );
		$host     = self::$dbManager->escape( $this->host );
		$password = self::$dbManager->escape( $newPassword );

		$sql = "SET PASSWORD FOR '$userName'@'$host' = PASSWORD('$password')";

		if( false == self::$dbManager->query( $sql ) )
			return false;

		$this->password  = $newPassword;

		return true;
	}

	public function delete() {
		if( false == $this->userName || false == $this->host )
			return false;

		$userName = self::$dbManager->escape( $this->userName );
		$host     = self::$dbManager->escape( $this->host );

		$sql = "DROP USER '$userName'@'$host'";

		return self::$dbManager->query( $sql );
	}

	public function getGlobalPrivileges() {
		if( false == $this->globalPrivileges )
			$this->globalPrivileges = new Privileges( $this->userName, $this->host );

		return $this->globalPrivileges->get();
	}

	public function setGlobalPrivileges($privileges) {
		if( false == $this->globalPrivileges )
			$this->globalPrivileges = new Privileges( $this->userName, $this->host );

		return $this->globalPrivileges->set( $privileges );
	}

	public function getDbPrivileges($dbName) {
		$dbName = trim( $dbName );
		if( false == isset( $this->dbPrivileges[$dbName] ) )
			$this->dbPrivileges[$dbName] = new DbPrivileges( $this->userName, $this->host, $dbName );

		return $this->dbPrivileges[$dbName]->get();
	}

	public function setDbPrivileges($dbName, $privileges) {
		$dbName = trim( $dbName );
		if( false == isset( $this->dbPrivileges[$dbName] ) )
			$this->dbPrivileges[$dbName] = new DbPrivileges( $this->userName, $this->host, $dbName );

		return $this->dbPrivileges[$dbName]->set( $privileges );
	}
}

/**
 * The user class for MySQL version 4.x
 */
class User_4x extends User {
	public function  __construct() {
		parent::__construct();
	}

	public function add() {
		if( false == $this->userName || false == $this->host )
			return false;

		$userName = self::$dbManager->escape( $this->userName );
		$host     = self::$dbManager->escape( $this->host );
		$password = self::$dbManager->escape( $this->password );

		$sql = "GRANT USAGE ON *.* TO '$userName'@'$host' IDENTIFIED BY '$password'";

		return self::$dbManager->query( $sql );
	}

	public function update($newUsername, $newHost = '%') {
		$username = self::$dbManager->escape( $newUsername );
		$host     = self::$dbManager->escape( $newHost );

		if( false == $this->userName || false == $this->host || false == $username || false == $host )
			return false;

		// @@todo update the user table to update the username and host
		$tableName = Privileges::$privilegesTable;
		$sql = "UPDATE $tableName SET `User` = '$username', `Host` = '$host' WHERE `User` = '{$this->userName}' AND `Host` = '{$this->host}'";
		self::$dbManager->query( $sql );

		// @@todo update the db tables to update the username and host
		$tableName = DbPrivileges::$privilegesTable;
		$sql = "UPDATE $tableName SET `User` = '$username', `Host` = '$host' WHERE `User` = '{$this->userName}' AND `Host` = '{$this->host}'";
		self::$dbManager->query( $sql );

		$this->userName  = $newUsername;
		$this->host      = $newHost;

		return true;
	}
}

/**
 * The user class for MySQL version 5.x
 */
class User_5x extends User {
	public function  __construct() {
		parent::__construct();
	}

	public function add() {
		if( false == $this->userName || false == $this->host )
			return false;

		$userName = self::$dbManager->escape( $this->userName );
		$host     = self::$dbManager->escape( $this->host );
		$password = self::$dbManager->escape( $this->password );

		$sql = "CREATE USER '$userName'@'$host' IDENTIFIED BY '$password'";

		return self::$dbManager->query( $sql );
	}

	public function update($newUsername, $newHost = '%') {
		$newUsername = self::$dbManager->escape( $newUsername );
		$newHost     = self::$dbManager->escape( $newHost );

		if( false == $newUsername || false == $newHost )
			return false;

		$sql = "RENAME USER '{$this->userName}'@'{$this->host}' TO '$newUsername'@'$newHost'";

		if( false == self::$dbManager->query( $sql ) )
			return false;

		$this->userName  = $newUsername;
		$this->host      = $newHost;

		return true;
	}
}