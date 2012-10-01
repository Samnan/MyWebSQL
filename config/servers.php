<?php
/**
 * This file is a part of MyWebSQL package
 * defining more that one server here will give user the option to select a server at login time 
 * Notes:
 *   Server list is used only when authentication type is LOGIN 
 *
 * @file:      config/servers.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */
 
	// add or remove list of servers below
	
	// please make sure you have the proper extensions enabled in your php config
	// to successfully connect to servers other than MySQL
	
	// valid drivers types are:
	// mysql4, mysql5, mysqli, sqlite, pgsql
	
	// for sqlite driver:
	//   'host' should be the folder name where sqlite databases are saved,
	//   'user' and 'password' options should be added for additional security

	$SERVER_LIST = array(
		'Localhost MySQL'           => array(
		                             'host'     => 'localhost',
		                             'driver'   => extension_loaded('mysqli') ? 'mysqli' : 'mysql5'
		                         ),
		'SQLite Databases'          => array(
		                             'host'     => 'c:/sqlitedb/',
		                             'driver'   => 'sqlite',
		                             'user'     => 'root',   // set this yourself
		                             'password' => 'sqlite'  // set this yourself
		                         ),
 		'Localhost PostgreSQL'     => array(
		                             'host'     => 'localhost',
		                             'driver'   => 'pgsql'
		                         ),
		/*'MySQL Proxy Server'  => array(
		                             'host'     => 'localhost:4040',
		                             'driver'   => 'mysql5'
		                         ),
		'MySQL 4'             => array(
		                             'host'     => 'localhost',
		                             'driver'   => 'mysql4'
		                         ),
		*/
	);
?>