<?php
/**
 * This file is a part of MyWebSQL package
 * codemirror2 editor functionality provider
 *
 * @file:      lib/editor/codemirror2.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function createSqlEditor() {
		$js = 'codemirror2,mysql';
		print '<link rel="stylesheet" type="text/css" href="cache.php?css=codemirror2" />';
		print "<script type=\"text/javascript\" language=\"javascript\" src=\"cache.php?script=$js\"></script><script type=\"text/javascript\" language=\"javascript\">
			function editorHotkey(code, fn) {
				$(document).bind('keydown', code, fn);
				$(document).bind('keydown', code, fn);
				$(document).bind('keydown', code, fn);
			}
			$(function() {\n";
		sqlEditorJs('commandEditor', 'initStart();');
		sqlEditorJs('commandEditor2');
		sqlEditorJs('commandEditor3');
		print "\n}); </script>";
	}

	function sqlEditorJs($id, $init='') {
		print $id.' = CodeMirror.fromTextArea(document.getElementById("'.$id.'"), { mode: "text/x-mysql",
				lineNumbers: true, matchBrackets: true, indentUnit: 3,
				height: "100%", tabMode : "default",
				tabFunction : function() { document.getElementById("nav_query").focus(); },
				onLoad : function() { '.$init.' }
			});';
	}
	
?>