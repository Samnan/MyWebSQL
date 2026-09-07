<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/backup.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        https://github.com/Samnan/MyWebSQL
 * @license    https://github.com/Samnan/MyWebSQL/license
 */

	function processRequest(&$db) {
		$object_list = $db->getObjectList();
		include_once(BASE_PATH . "/config/backups.php");

		$filename = isset($_REQUEST['filename']) ? v($_REQUEST['filename']) : BACKUP_FILENAME_FORMAT;
		$message = is_dir( BACKUP_FOLDER ) && is_writable( BACKUP_FOLDER ) ?
			'<div class="message ui-state-default">'.__('Select objects to include in backup').'</div>' :
			'<div class="message ui-state-error">'.__('WARNING').': '.__('Backup folder does not exist or is not writable').'</div>';
		$replace = array(
			'MESSAGE' => $message,
			'FILENAME' => htmlspecialchars($filename)
		);

		$folder = $db->name();

		echo view( array($folder.'/backup', 'backup'), $replace, $object_list);
	}

	/**
	 * Progress of a running backup, polled by the dialog through status.php while the
	 * backup request itself is still busy writing the dump.
	 *
	 * NOTE: status.php is a minimal bootstrap, neither v() nor __() exist here.
	 */
	function getModuleStatus( $id ) {
		include_once(BASE_PATH . "/lib/backupstate.php");

		$status = array('c' => 0, 'r' => 0, 's' => 0, 'state' => 'unknown');

		$token = BackupState::sanitizeToken( $id );
		$owned = ( $token !== false && $token === Session::get('backup', 'token') );

		// this is polled once per second, do not sit on the session lock while doing so
		Session::close();

		// only report on the backup started by this very session
		if ( !$owned )
			return $status;

		$data = BackupState::read( $token );
		if ( !is_array($data) ) {
			// the backup request has not written its first update yet
			$status['s'] = 1;
			$status['state'] = 'starting';
			return $status;
		}

		$percent = 0;
		if ( $data['total'] > 0 )
			$percent = (int) floor( $data['done'] / $data['total'] * 100 );
		if ( $data['state'] == 'running' && $percent > 99 )
			$percent = 99;		// the last object is still being written
		if ( $data['state'] == 'done' )
			$percent = 100;

		$status['c'] = $percent;
		$status['s'] = 1;
		$status['r'] = $data['state'] == 'running' ? 0 : 1;
		$status['state'] = $data['state'];
		$status['elapsed'] = (int) ( microtime(true) - $data['started'] );

		foreach( array('done', 'total', 'type', 'object', 'rows', 'totalrows', 'bytes', 'file', 'message') as $key )
			$status[$key] = isset($data[$key]) ? $data[$key] : '';

		$status['size'] = isset($data['size']) ? $data['size'] : 0;

		return $status;
	}

?>