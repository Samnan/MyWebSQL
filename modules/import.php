<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/import.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
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
				include(BASE_PATH . "/lib/sqlparser.php");
				$parser = new sqlParser($db);
				$parser->stopOnError(v($_REQUEST['ignore_errors']) == 'yes' ? FALSE : TRUE);
				//$parser->setCallback( 'report_progress', $module_id );
				//$parser->collectStats(v($_REQUEST['stats']) == 'yes');
				
				Session::close();
				$result = $parser->parse($_FILES['impfile']['tmp_name'], $_FILES['impfile']['size'], $_FILES['impfile']['name']);

				$executed = $parser->getExecutedQueries();
				$failed = $parser->getFailedQueries();
				if (!$result || $executed > 0 || $failed > 0) {
					$message .= '<div class="message ui-state-default">'.str_replace('{{NUM}}', $executed,  __('{{NUM}} queries successfully executed'));
					$message .= '<br />[ ' . str_replace('{{NUM}}', $parser->getRowsAffected(), __('{{NUM}} record(s) were affected')) . '. ]</div>';
					if ($failed > 0) {
						$message_tmp = ($failed > 1) ? str_replace('{{NUM}}', $failed, __('{{NUM}} queries failed to execute')) : __('Error occurred while executing the query');
						$message .= '<div class="message ui-state-error">'.$message_tmp.'</div>';
						if ($failed == 1) {
							$message .= '<div class="message ui-state-highlight">' . htmlspecialchars($parser->getError()) . '</div>';
							$message .= '<div class="sql-text ui-state-error">' . htmlspecialchars($parser->getLastQuery()) . '</div>';
						}
					}
				}
				else
					$message .= '<div class="message ui-state-default">'.__('No queries were executed during import').'.</div>';
			}
			else
				$message .= '<div class="message ui-state-error">'.__('File upload failed. Please try again').'.</div>';
			
			$importDone = TRUE;
		}
		
		if (!$importDone) {
			$message = '<div class="message ui-state-default">'.str_replace('{{SIZE}}', $max_upload_size_text, __('Maximum upload filesize is {{SIZE}}'));
			$message .= '<br/>' . str_replace('{{LIST}}', valid_import_files(), __('Supported filetypes / extensions are: ({{LIST}})')) . '</div>';
		} else {
			$refresh = '1';
		}
		
		$replace = array( 'MESSAGE' => $message, 'MAX_SIZE' => $max_upload_size, 'REFRESH' => $refresh );
		echo view( 'import', $replace, array( 'progress' => phpCheck(5.4) ) );
	}

	function valid_import_files() {
		$files = '*.sql, *.txt';
		if (function_exists('bzopen'))
			$files .= ', *.bz, *.bzip, *.bz2';
		if (function_exists('gzopen'))
			$files .= ', *.gz, *.gzip';
		return $files;
	}
	
	// reports file upload progress during import
	function getModuleStatus( $id ) {
		$key = "upload_progress_import";
		$status = array('c' => 0, 'r' => 0, 's' => 0);
		if ( isset( $_SESSION[$key] ) ) {
			$status['c'] = (int) ( $_SESSION[$key]['bytes_processed'] / $_SESSION[$key]['content_length'] * 100 );
			$status['s'] = 1;
		}
		return $status;
	}
	
	// reports sql import status after file uploads
	function report_progress( $module_id, $executed ) {
		$st = Session::get( 'status', $module_id );
		Session::set( 'status', $module_id, $executed );
	}
?>