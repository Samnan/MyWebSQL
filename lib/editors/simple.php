<?php
/**
 * This file is a part of MyWebSQL package
 * simple text editor functionality provider
 *
 * @file:      lib/editor/simple.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function createSqlEditor() {
		print "<script type=\"text/javascript\" language=\"javascript\" src=\"cache.php?script=texteditor\"></script><script type=\"text/javascript\" language=\"javascript\">
			function editorHotkey(code, fn) {
				$('#commandEditor').bind('keydown', code, fn);
				$('#commandEditor2').bind('keydown', code, fn);
				$('#commandEditor3').bind('keydown', code, fn);
			}
			$(function() {
				commandEditor = new textEditor(\"#commandEditor\");
				commandEditor2 = new textEditor(\"#commandEditor2\");
				commandEditor3 = new textEditor(\"#commandEditor3\");
				initStart();
			}); </script>";
	}
?>