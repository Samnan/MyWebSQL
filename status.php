<?php
/**
 * This file is a part of MyWebSQL package
 * 
 * @file:      status.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */
	
	define('BASE_PATH', dirname(__FILE__));

	header("Content-Type: text/html;charset=utf-8");
	include_once(BASE_PATH . "/lib/session.php");
	Session::init();
	
	include_once(BASE_PATH . "/config/config.php");

	if (defined("TRACE_FILEPATH") && TRACE_FILEPATH && defined("TRACE_MESSAGES") && TRACE_MESSAGES)
		ini_set("error_log", TRACE_FILEPATH);

	$status = '[]';
	
	if ( !isset($_REQUEST['type']) || !isset($_REQUEST['id']) )
		die($status);
	
	$module = BASE_PATH . '/modules/'.$_REQUEST['type'].'.php';
	if (ctype_alpha($_REQUEST['type']) && file_exists($module)) {
		include($module);
		if ( function_exists('getModuleStatus') )
			$status = json_encode( getModuleStatus($_REQUEST['id']) );
	}
	
	/*
	 * return object should contain the following information
	 * c = completion ratio
	 * r = refresh objects ( probably operation complete or db changed )
	 * s = status flag ( 1=ok, 0=error )
	 */ 
	echo $status;
?>