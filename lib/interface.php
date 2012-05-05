<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      lib/interface.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function createMenuBar() {
		$themeMenu = '';
		$langMenu = '';
		$editorMenu = '';
		$langList = array();
		include ("config/themes.php");
		foreach($THEMES as $themeId => $theme) {
			if (THEME_PATH == $themeId)
				$themeMenu .= '<li><a class="check" href="javascript:setPreference(\'theme\', \''.$themeId.'\')">'.$theme.'</a></li>';
			else
				$themeMenu .= '<li><a href="javascript:setPreference(\'theme\', \''.$themeId.'\')">'.$theme.'</a></li>';
		}
		
		$langList = getLanguageList();
		foreach ($langList as $lang => $name) {
			if (LANGUAGE == $lang)
				$langMenu .= '<li><a class="check" href="javascript:setPreference(\'lang\', \''.$lang.'\')">'.$name.'</a></li>';
			else
				$langMenu .= '<li><a href="javascript:setPreference(\'lang\', \''.$lang.'\')">'.$name.'</a></li>';
		}

		include ("config/editors.php");
		foreach($CODE_EDITORS as $editorId => $name) {
			if (SQL_EDITORTYPE == $editorId)
				$editorMenu .= '<li><a class="check" href="javascript:setPreference(\'editor\', \''.$editorId.'\')">'.$name.'</a></li>';
			else
				$editorMenu .= '<li><a href="javascript:setPreference(\'editor\', \''.$editorId.'\')">'.$name.'</a></li>';
		}

		$replace = array(
			'THEMES_MENU' => $themeMenu,
			'LANGUAGE_MENU' => $langMenu,
			'EDITOR_MENU' => $editorMenu
		);
		echo view('menubar', $replace);
	}

	function createDatabaseTree(&$db, $dblist=array()) {
		$folder = Session::get('db', 'driver') . '/tree';
		if (getDbName())
			echo view(array($folder.'/objtree', 'objtree'), array(), $db->getObjectList());
		else {
			print '<ul id="tablelist" class="dblist">';
			foreach($dblist as $dbname)
				print '<li><span class="odb"><a href="javascript:dbSelect(\''.$dbname.'\')">'.htmlspecialchars($dbname).'</a></span>';
			print '</ul>';
		}
	}

	function createContextMenus() {
		echo view('menuobjects');
	}

	function updateSqlEditor() {
		$editor_file = 'lib/editors/' . SQL_EDITORTYPE . '.php';
		if ( !file_exists( $editor_file ) )
			return false;
		
		include( $editor_file );
		createSqlEditor();
	}

	function setupHotkeys() {
		if (!defined('HOTKEYS_ENABLED') || !HOTKEYS_ENABLED)
			return false;

		print "<script type=\"text/javascript\" language=\"javascript\" src=\"cache.php?script=hotkeys\"></script><script type=\"text/javascript\" language=\"javascript\"> $(function() {\n";
		include ("config/keys.php");
		foreach ($DOCUMENT_KEYS as $name => $func) {
			$code = $KEY_CODES[$name][0];
			print "$(document).bind('keydown', '$code', function (evt) { $func; return false; });\n";
		}
		
		// if shortcuts are defined for sql editor, generate script for them too
		$var = strtoupper(SQL_EDITORTYPE). '_KEYS';
		if ( isset( ${$var} ) && is_array( ${$var} ) ) {
			$EDITOR_KEYS = ${$var};
			foreach ( $EDITOR_KEYS as $name => $func ) {
				$code = $KEY_CODES[$name][0];
				print "editorHotkey('$code', function (evt) { $func; return false; } );\n";
			}
		}
		print " }); </script>";
		return true;
	}
?>