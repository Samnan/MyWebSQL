<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/importtbl.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		$importDone = FALSE;
		$message = '';
		$refresh = '0';
		$max_upload_size = min(bytes_value(ini_get('post_max_size')), bytes_value(ini_get('upload_max_filesize'))) / 1024;
		$max_upload_size_text = ($max_upload_size < 1024) ? $max_upload_size.'KB' : ($max_upload_size/1024).' MB';
		
		if (isset($_FILES['impfile'])) {
			if (v($_FILES['impfile']['tmp_name']) != '' && file_exists($_FILES['impfile']['tmp_name'])) {
				$module_id = createModuleId( 'import' );
				include("lib/import/import.php");
				
				$type = 'csv';
				$imptype = v($_REQUEST['$imptype']);
				if(in_array($imptype, DataImport::types()))
					$type = $imptype;
						
				$importer = DataImport::factory($db, $type);
				$options = extract_vars($_REQUEST, $importer->options());
				$result = $importer->importTable($_FILES['impfile']['tmp_name'], $options);

				$failed = $importer->getFailedQueries();
				$affected = $importer->getRowsAffected();
				if (!$result || $affected > 0 || $failed > 0) {
					$message .= '<div class="success">[ ' . str_replace('{{NUM}}', $affected, __('{{NUM}} record(s) were affected')) . '. ]</div>';
					if ($failed > 0) {
						$message_tmp = ($failed > 1) ? str_replace('{{NUM}}', $failed, __('{{NUM}} queries failed to execute')) : __('Error occurred while executing the query');
						$message .= '<div class="warning">'.$message_tmp.'</div>';
						if ($failed == 1) {
							$message .= '<div class="sql_error">' . htmlspecialchars($importer->getLastQuery()) . '</div>';
							$message .= '<div class="message">' . htmlspecialchars($importer->getError()) . '</div>';
						}
					}
				}
				else
					$message .= '<div class="success">'.__('No queries were executed during import').'.</div>';
			}
			else
				$message .= '<div class="warning">'.__('File upload failed. Please try again').'.</div>';
			
			$importDone = TRUE;
		}
		
		if (!$importDone) {
			$message = '<div class="sql_text">'.str_replace('{{SIZE}}', $max_upload_size_text, __('Maximum upload filesize is {{SIZE}}'));
			$message .= '<br/>' . str_replace('{{LIST}}', valid_import_files(), __('Supported filetypes / extensions are: ({{LIST}})')) . '</div>';
		} else {
			$refresh = '1';
		}
		
		include('lib/html.php');
		$tables = html::arrayToOptions($db->getTables(), '', true, '');
		$replace = array( 'MESSAGE' => $message, 'MAX_SIZE' => $max_upload_size, 'REFRESH' => $refresh,
			'TABLE_LIST' => $tables );
		echo view('importtbl', $replace);
	}

	function valid_import_files() {
		$files = '*.csv, *.txt';
		/*if (function_exists('bzopen'))
			$files .= ', *.bz, *.bzip, *.bz2';
		if (function_exists('gzopen'))
			$files .= ', *.gz, *.gzip';*/
		return $files;
	}
?>