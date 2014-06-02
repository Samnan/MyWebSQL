<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/infoserver.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		if (!$db->hasServer()) {
			echo view('infoserverless', array());
			return;
		}

		if ($db->queryVariables()) {
			$folder = $db->name();

			include( find_view( array($folder.'/templates/variables', 'templates/variables') ) );
			$vars = parseVariables($db);

			$replace = $vars + array('JS' => '');
			if (getDbName() == '')		// no database selected, hide menus that belong to a db only
				$replace['JS'] = 'parent.$("#main-menu").find(".db").hide();';
			echo view(array($folder.'/infoserver', 'infoserver'), $replace);
		}
	}

?>