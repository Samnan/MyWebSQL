<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/infoserver.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		if (!$db->hasServer()) {
			echo view('infoserverless', array());
			return;
		}
		
		$ver = $ver2 = $cs1 = $cs2 = $cs3 = $cs4 = "";
		if ($db->query("show variables")) {
			while($row = $db->fetchRow())
			{
				switch($row[0]) {
					case "version": $ver = $row[1]; break;
					case "version_comment": $ver2 = $row[1]; break;
					case "character_set_server": $cs1 = $row[1]; break;
					case "character_set_client": $cs2 = $row[1]; break;
					case "character_set_database": $cs3 = $row[1]; break;
					case "character_set_results": $cs4 = $row[1]; break;
				}
			}
			
			$replace = array('SERVER_VERSION' => $ver,
								'SERVER_COMMENT' => $ver2,
								'SERVER_CHARSET' => $cs1,
								'CLIENT_CHARSET' => $cs2,
								'DATABASE_CHARSET' => $cs3,
								'RESULT_CHARSET' => $cs4,
								'JS' => ''
							);
			if (getDbName() == '')		// no database selected, hide menus that belong to a db only
				$replace['JS'] = 'parent.$("#main-menu").find(".db").hide();';
			echo view('infoserver', $replace);
		}
	}

?>