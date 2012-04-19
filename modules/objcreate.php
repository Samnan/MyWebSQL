<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/objcreate.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db)	{
		$type = "note";
		if (isset($_REQUEST["objinfo"])) {
			$msg = createDatabaseObject($db, $_REQUEST["id"], $_REQUEST["objinfo"]);
			if (!$msg) {		// msg will be returned only if error (bad programming guys, dont learn from it !)
				$msg = __('The command executed successfully');
				$type = "success";
			}
			else
				$type="warning";
		}
		else
			$msg = __('Any existing object with the same name should be dropped manually before executing the creation command').'!';
		displayCreateObjectForm($msg, $type);
	}

	function displayCreateObjectForm($msg, $type="warning") {
		$id = $_REQUEST["id"];
		if (isset($_REQUEST["objinfo"]))
			$objInfo = htmlspecialchars($_REQUEST["objinfo"]);
		else
			$objInfo = getObjectCreateCommand($id);
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
								'EDITOR_OPTIONS' => $editor_options
							);

		echo view('objcreate', $replace);
	}

	function getObjectCreateCommand($id) {
		$folder = 'templates/' . Session::get('db', 'driver');

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