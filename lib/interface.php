<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      lib/interface.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function getMenuBarHTML() {
		$themeMenu = '';
		$langMenu = '';
		$editorMenu = '';
		$langList = array();
		include (BASE_PATH . "/config/themes.php");
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

		include (BASE_PATH . "/config/editors.php");
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
		return view('menubar', $replace);
	}

	function getDatabaseTreeHTML(&$db, $dblist=array()) {
		$folder = $db->name();
		if (getDbName()) {
			$objects = $db->getObjectList( true );
			// sort the object listing based on user preferences
			$sort = Options::get('ui-tables-sort');
			if ($sort && ( count($objects['tables']) > 1 ) )
				$objects['tables'] = sortTableListing( $objects['tables'], $sort );
			return view(array($folder.'/objtree', 'objtree'), array(), $objects);
		}

		return view(array($folder.'/dbtree', 'dbtree'), array(), $dblist);
	}

	function getContextMenusHTML() {
		return view('menuobjects');
	}

	function updateSqlEditor() {
		$editor_file = BASE_PATH . '/lib/editors/' . SQL_EDITORTYPE . '.php';
		if ( !file_exists( $editor_file ) )
			return false;

		include( $editor_file );
		createSqlEditor();
	}

	function getHotkeysHTML() {
		if (!defined('HOTKEYS_ENABLED') || !HOTKEYS_ENABLED)
			return '';
		$hotkeysHTML =  "<script type=\"text/javascript\" language=\"javascript\" src=\"cache.php?script=hotkeys\"></script><script type=\"text/javascript\" language=\"javascript\"> $(function() {\n";
		include (BASE_PATH . "/config/keys.php");
		foreach ($DOCUMENT_KEYS as $name => $func) {
			$code = $KEY_CODES[$name][0];
			$hotkeysHTML .=  "$(document).bind('keydown', '$code', function (evt) { $func; return false; });\n";
		}

		// if shortcuts are defined for sql editor, generate script for them too
		$var = strtoupper(SQL_EDITORTYPE). '_KEYS';
		if ( isset( ${$var} ) && is_array( ${$var} ) ) {
			$EDITOR_KEYS = ${$var};
			foreach ( $EDITOR_KEYS as $name => $func ) {
				$code = $KEY_CODES[$name][0];
				$hotkeysHTML .=  "editorHotkey('$code', function (evt) { $func; return false; } );\n";
			}
		}
		$hotkeysHTML .=  " }); </script>";
		return $hotkeysHTML;
	}

	function sortTableListing($tables, $sort) {
		$ret = array();
		for ($i = 0; $i<count($tables); $i++){
			for ($j = $i+1; $j<count($tables); $j++) {
				if($sort == 'time' && ( strcmp( $tables[$i][3], $tables[$j][3] ) < 0 ) ) {
					$tmp = $tables[$i];
					$tables[$i] = $tables[$j];
					$tables[$j] = $tmp;
				}
			}
		}

		return $tables;
	}
?>