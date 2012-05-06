<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/viewblob.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */
	function processRequest(&$db) {
		
		// verify that the blob data is from the existing and valid query
		$queryCode = v($_REQUEST['query']);
		if ( Session::get('select', 'query') == "" || md5(Session::get('select', 'query')) != $queryCode) {
			echo view('invalid_request');
			return;
		}

		$id = v($_REQUEST["id"]);
		$name = v($_REQUEST["name"]);
		$isEditable = isBlobEditable();		
		$table = Session::get('select', 'unique_table'); // Session::get('select', 'table')
		$message = ''; 

		if ( v($_REQUEST['act']) == 'save' && $isEditable && count($_FILES) > 0 && isset($_FILES['blobdata']) ) {
			$result = saveBlobData($db, $table, $id, $name);
			$message = $result ? '<div class="message ui-state-default">'.__('Blob data saved').'</div>'
				: '<div class="message ui-state-error">'.__('Failed to save blob data').'</div>';
			unset($_REQUEST["blobtype"]);
		}

		include("config/blobs.php");
		$bType = ( v($_REQUEST["blobtype"]) && array_key_exists(v($_REQUEST["blobtype"]), $blobTypes) ) ? v($_REQUEST["blobtype"]) : "txt";

		// @todo: optimize. this should always fetch one row
		$blobOptions = '';
		$query = Session::get('select', 'query');
		
		if ($table == "")
			$applyLimit = true;
		else
			$applyLimit = strpos($query, "limit ");

		if ($applyLimit == false)
			$query .= " limit $id, 1";

		if (!$db->query($query) || $db->numRows() == 0) {
			echo view('error_page');
			return;
		}

		$row = ($applyLimit==false ? $db->fetchRow() : $db->fetchSpecificRow($id));

		// show as image etc ...
		if ($bType && v($_REQUEST["show"]) && $blobTypes[$bType][2]) {
			ob_end_clean();
			buffering_start();
			header($blobTypes[$bType][2]);
			print $row[$name];
			return true;
		}

		foreach($blobTypes as $k=>$v) {
			if ($bType == $k)
				$blobOptions .= "<option value='$k' selected=\"selected\">$v[0]</option>\n";
			else
				$blobOptions .= "<option value='$k'>$v[0]</option>\n";
		}

		// try to show the blob data as specified type
		if ($bType && $blobTypes[$bType] && v($blobTypes[$bType][3])) {
			if (strpos($blobTypes[$bType][3], "#link#") !== false)
				$blobData = str_replace("#link#", "?q=wrkfrm&type=viewblob&show=1&id=$id&name=$name&blobtype=$bType&query=".urlencode($queryCode), $blobTypes[$bType][3]);
			//else if (strpos($blobTypes[$bType][3], "#src#") !== false)
			//	print str_replace("#src", $row[$name], $blobTypes[$bType][3]);
			else
				$blobData = htmlspecialchars($row[$name]);
		} else if ($bType && $blobTypes[$bType] && v($blobTypes[$bType][4])) {
			$func = $blobTypes[$bType][4];
			$blobData = htmlspecialchars(print_r($func($row[$name]), 1));
		} else
			$blobData = htmlspecialchars($row[$name]);

		$toolbar = $isEditable ? view('viewblob_toolbar', array(
			'BLOBOPTIONS' => $blobOptions,
		)) : '<div class="message ui-state-default">' . __('Blob data is not editable') . '</div>';
		
		$replace = array('ID' => $id,
								'NAME' => $name,
								'BLOBOPTIONS' => $blobOptions,
								'BLOBDATA' => $blobData,
								'TABLE' => $table == "" ? "" : htmlspecialchars($table),
								'QCODE' => md5(Session::get('select', 'query')), // this will help identify that the blob data belongs to this query
								'BLOB_TOOLBAR' => $toolbar,
								'MESSAGE' => $message
							);
		echo view('viewblob', $replace);
	}
	
	function isBlobEditable() {
		$table = Session::get('select', 'unique_table'); // Session::get('select', 'table')
		$pkeyCount = count(Session::get('select', 'pkey'));
		$uKeyCount = count(Session::get('select', 'ukey'));

		if ($table != "" && ( $pkeyCount > 0 || $uKeyCount > 0 ) )
			return true;
		
		return false;  		
	}
	
	function saveBlobData(&$db, $table, $id, $field) {
		$record = $id;
		$file = $_FILES['blobdata']['tmp_name'];
		$blobData = file_get_contents($file);
		
		if (empty($blobData))
			return false;
	
		// fetch the record to build update query
		$query = Session::get('select', 'query');
		if (Session::get('select', 'unique_table') == "")
			$applyLimit = true;
		else
			$applyLimit = strpos($query, "limit ");

		if ($applyLimit == false)
			$query .= " limit $id, 1";

		if (!$db->query($query) || $db->numRows() == 0) {
			echo view('error_page');
			return;
		}

		$bq = $db->getBackQuotes();
		
		$row = ($applyLimit==false ? $db->fetchRow() : $db->fetchSpecificRow($id));
		$where = array();
		if (count(Session::get('select', 'pkey')) > 0) {
			$pkey = Session::get('select', 'pkey');
			foreach($pkey as $key)
				$where[] = "`" . $key . "`='" . $db->escape($row[$key]) . "'";
		}
		else if (count(Session::get('select', 'ukey')) > 0) {
			$ukey = Session::get('select', 'ukey');
			foreach($ukey as $key)
				$where[] = $bq . $key . "$bq='" . $db->escape($row[$key]) . "'";
		}
		else {
		}
		
		$sql = "update $bq" . $table . "$bq set $bq$field$bq='" . $db->escape($blobData) . "' where " . implode(' AND ', $where);
		return $db->query($sql);
	}

?>