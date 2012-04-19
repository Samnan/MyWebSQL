<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/export.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		$db_tables = $db->getTables();
		$db_views = $db->getViews();
		$db_procedures = $db->getProcedures();
		$db_functions = $db->getFunctions();
		$db_triggers = $db->getTriggers();
		$db_events = $db->getEvents();

		$replace = array('TABLELIST' => json_encode($db_tables),
						'VIEWLIST' => json_encode($db_views),
						'PROCLIST' => json_encode($db_procedures),
						'FUNCLIST' => json_encode($db_functions),
						'TRIGGERLIST' => json_encode($db_triggers),
						'EVENTLIST' => json_encode($db_events)
						);
		echo view('export', $replace);
	}

?>