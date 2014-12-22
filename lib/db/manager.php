<?php
/**
 * This file is a part of MyWebSQL package
 * Provides a generic wrapper for database connection functionality
 *
 * @file:      lib/db/manager.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (defined("CLASS_COMMON_DB_INCLUDED"))
	return true;

define("CLASS_COMMON_DB_INCLUDED", "1");

class DbManager {
	var $conn;
	var $errMsg;

	function DbManager() {
	}

	function connect($server, $user, $password, $db="") {
		$host   = $server['host'];
		$driver = $server['driver'];

		log_message('connecting to ['.$host.'] driver = ['.$driver.']');

		$lib = BASE_PATH . '/lib/db/'.$driver.'.php';
		include_once($lib);
		$class = 'DB_' . ucfirst( str_replace('/', '_', $driver) );
		$db = new $class();
		$db->setAuthOptions($server);

		$result = $db->connect($host, $user, $password);
		if (!$result) {
			$this->errMsg = $db->getError();
			return false;
		}
		$db->disconnect();
		return true;
	}

	// required for proper functionality
	function disconnect() {
	}

	function getError() {
		return $this->errMsg;
	}
}

function replace_single_quotes($s) {
	return str_replace("''", "'", $s);
}

?>