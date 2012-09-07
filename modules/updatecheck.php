<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/updatecheck.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	// returns json output for online update check
	function processRequest(&$db) {
		ob_end_clean();
		buffering_start();
		$link = "http://mywebsql.net/updates.php?j=1&" . "c=MyWebSQL&l=" . urlencode(LANGUAGE) 
			. "&v=" . urlencode(APP_VERSION) . "&t="  . urlencode(THEME_PATH);

		$output = "";
		if (ini_get("allow_url_fopen"))
			$output = file_get_contents($link);
		else
			$output = curl_get($link);

		Session::set('updates', 'check', '1');

		echo($output);
		$db->disconnect();
		buffering_flush();
		die();
	}
?>