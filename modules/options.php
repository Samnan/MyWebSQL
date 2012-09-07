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
		$pages = array(	"editing"=>'Editing',
								"misc"=>'Miscellaneous',
						);

		if ( v($_REQUEST["p"]) && array_key_exists(v($_REQUEST["p"]), $pages) )
			$page = $_REQUEST["p"];
		else
			$page = "editing";

		$links = '';
		foreach($pages as $x=>$y) {
			if ($page == $x)
				$links .= "<tr><td class='sel'>
					<table border=0 cellpadding=\"0\" cellspacing=\"0\">
						<tr><td><img border=\"0\" align=\"middle\" src='options/t_$x".".gif' alt=\"\" alt=\"\" /></td>
						<td nowrap\"nowrap\">&nbsp;$y</td></tr></table>
					</td></tr>\n";
			else
				$links .= "<tr><td class='norm' onmouseover=\"hoverlink(this,0)\" onmouseout=\"hoverlink(this,1)\" onclick=\"showlink('$x')\">
					<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
						<tr><td><img border=\"0\" align=\"middle\" src='options/t_$x".".gif' alt=\"\" /></td>
						<td nowrap\"nowrap\">&nbsp;$y</td></tr></table>
					</td></tr>\n";
		}

		$content = view("options.$page");

		$replace = array('LINKS' => $links,
								'CONTENT' => $content,
								'PAGE' => $page
							);

		echo view('options', $replace);

	}

?>