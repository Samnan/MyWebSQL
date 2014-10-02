<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/objcreate.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db)	{
		// $_REQUEST[name] may contain the schema id, e.g. 'public.Tables'
		$refresh = false;
		$type = "message ui-state-highlight";
		if (isset($_REQUEST["objinfo"])) {
			$msg = createDatabaseObject($db, $_REQUEST["id"], $_REQUEST["objinfo"]);
			if (!$msg) {		// msg will be returned only if error (bad programming guys, dont learn from it !)
				$msg = __('The command executed successfully');
				$type = "message ui-state-default";
				$refresh = true;
			}
			else
				$type="message ui-state-error";
		}
		else
			$msg = __('Any existing object with the same name should be dropped manually before executing the creation command')
				.'!<br />'
				.__('Enter command for object creation');
		displayCreateObjectForm($db, $msg, $type, $refresh);
	}

	function displayCreateObjectForm(&$db, $msg, $type, $refresh) {
		$id = $_REQUEST["id"];
		if (isset($_REQUEST["objinfo"]))
			$objInfo = htmlspecialchars($_REQUEST["objinfo"]);
		else
			$objInfo = getObjectCreateCommand($db, $id);
		print "</textarea></td></tr>";

		$min = file_exists('js/min/minify.txt');
		$js = $min ? 'codemirror' : 'editor/codemirror';
		$editor_link = "<script type=\"text/javascript\" language=\"javascript\" src=\"cache.php?script=$js\"></script>";
		$editor_options = $min ? 'basefiles: ["js/min/codemirror_base.js"]' : 'parserfile: "mysql.js", path: "js/editor/"';

		$replace = array('ID' => htmlspecialchars($id),
								'MESSAGE' => $msg,
								'MESSAGE_TYPE' => $type,
								'OBJINFO' => $objInfo,
								'EDITOR_LINK' => $editor_link,
								'EDITOR_OPTIONS' => $editor_options,
								'REFRESH' => $refresh ? '1' : '0'
							);

		echo view('objcreate', $replace);
	}

	function getObjectCreateCommand(&$db, $id) {
		$folder = $db->name() . '/templates';

		$x = '';
		switch($id) {
			case 0:
				$x = view(array($folder.'/table', 'templates/table')); break;
			case 1:
				$x = view(array($folder.'/view', 'templates/view')); break;
			case 2:
				$x = view(array($folder.'/procedure', 'templates/procedure')); break;
			case 3:
				$x = view(array($folder.'/function', 'templates/function')); break;
			case 4:
				$x = view(array($folder.'/trigger', 'templates/trigger')); break;
			case 5:
				$x = view(array($folder.'/event', 'templates/event')); break;
			case 6:
				$x = view(array($folder.'/schema', 'templates/schema')); break;
		}
		return htmlspecialchars($x);
	}

	function createDatabaseObject(&$db, $id, $info) {
		$cmd = trim($info);
		if (strtolower(substr($cmd, 0, 6)) != "create")
			return __('Only create commands are accepted');

		if (!$db->query($cmd))
			return htmlspecialchars($db->getError());

		$warnings = $db->getWarnings();
		if (count($warnings) > 0) {
			foreach($warnings as $code=>$warning)
				return htmlspecialchars($warning); // return the first warning (for this module always true)
		}

		return "";
	}

?>