<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/help.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		$p = v($_REQUEST["p"], 'queries');
		
		$pages = array(
						"queries"=>'Executing queries',
						"results"=>'Working with results',
						"keyboard"=>'Keyboard shortcuts',
						"prefs"=>'Preferences',
						"misc"=>'Miscellaneous',
						"credits"=>'Credits',
						"about"=>'About'
						);

		if ( !array_key_exists($p, $pages) )
			$p = "queries";

		$contents = view("help/$p");

		$replace = array(
			'PROJECT_SITEURL' => PROJECT_SITEURL,
			'CONTENT' => $contents
		);
		
		echo view('help', $replace, array('pages' => $pages, 'page' => $p) );
	}

?>