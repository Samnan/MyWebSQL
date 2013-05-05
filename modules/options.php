<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/options.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		$p = v($_REQUEST["p"], 'ui');

		$pages = array(
			'results' => __('Results'),
			'editing' => __('Record Editing'),
			'misc' => __('Miscellaneous')
		);

		// for mysql there are some extra options
		if ($db->name() == 'mysql') {
			$pages = array('ui' => __('Interface')) + $pages;
		}

		if ( !array_key_exists($p, $pages) )
			$p = key($pages);

		$content = view("options/$p");

		$replace = array('CONTENT' => $content);

		echo view('options', $replace, array('pages' => $pages, 'page' => $p) );

	}

?>