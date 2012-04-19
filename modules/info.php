<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/info.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	if (getDbName() != '')
		include('infodb.php');
	else
		include('infoserver.php');

?>