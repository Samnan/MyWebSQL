<?php
/**
 * This file is a part of MyWebSQL package
 * tracks the progress of a server side backup, so that the browser can poll it
 * while the backup request is still running (see status.php / modules/backup.php)
 *
 * @file:      lib/backupstate.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        https://github.com/Samnan/MyWebSQL
 * @license    https://github.com/Samnan/MyWebSQL/license
 */

if (!defined("CLASS_BACKUPSTATE_INCLUDED"))
{
	define("CLASS_BACKUPSTATE_INCLUDED", "1");

	// NOTE: this class is also loaded from status.php, where neither v() nor __()
	//       are available. Keep it free of those helpers.

	class BackupState {
		private $token;
		private $path;
		private $data;
		private $last_write = 0;
		private $write_interval = 0.4;	// seconds between two throttled updates
		private $rows_base = 0;			// rows counted before the current object

		// only simple tokens are accepted, they end up in a file name
		public static function sanitizeToken( $token ) {
			if ( !is_string($token) )
				return false;
			return preg_match('/^[A-Za-z0-9]{8,40}$/', $token) ? $token : false;
		}

		public static function folder() {
			$dir = BASE_PATH . '/tmp/';
			if ( is_dir($dir) && is_writable($dir) )
				return $dir;
			return rtrim(sys_get_temp_dir(), '/' . DIRECTORY_SEPARATOR) . '/';
		}

		public static function statePath( $token ) {
			return self::folder() . 'backup-' . $token . '.json';
		}

		// returns the state array, or false when there is nothing (yet) to report
		public static function read( $token ) {
			$token = self::sanitizeToken( $token );
			if ( $token === false )
				return false;

			$path = self::statePath( $token );
			if ( !file_exists($path) )
				return false;

			$raw = @file_get_contents( $path );
			if ( $raw === false || $raw === '' )
				return false;

			$data = json_decode( $raw, true );
			// a half written file is not an error, the caller simply keeps the previous values
			return is_array($data) ? $data : false;
		}

		// removes state files left behind by aborted requests
		public static function cleanup( $max_age = 86400 ) {
			$files = @glob( self::folder() . 'backup-*.json' );
			if ( !is_array($files) )
				return;
			$now = time();
			foreach( $files as $file ) {
				if ( $now - @filemtime($file) > $max_age )
					@unlink( $file );
			}
		}

		public function __construct( $token ) {
			$this->token = $token;
			$this->path = self::statePath( $token );
			$this->data = array(
				'state'     => 'running',
				'db'        => '',
				'file'      => '',
				'total'     => 0,
				'done'      => 0,
				'type'      => '',
				'object'    => '',
				'rows'      => 0,
				'totalrows' => 0,
				'bytes'     => 0,
				'started'   => microtime(true),
				'updated'   => microtime(true),
				'message'   => ''
			);
		}

		public function begin( $total, $db_name, $file_name ) {
			$this->data['total'] = (int) $total;
			$this->data['db'] = $db_name;
			$this->data['file'] = $file_name;
			$this->write( true );
		}

		// moves on to the next object of the backup
		public function step( $type, $label ) {
			$this->data['done']++;
			$this->data['type'] = $type;
			$this->data['object'] = $label;
			$this->rows_base = $this->data['totalrows'];
			$this->data['rows'] = 0;
			$this->write( true );
		}

		// number of rows exported so far for the current object
		public function rows( $count ) {
			$this->data['rows'] = (int) $count;
			$this->data['totalrows'] = $this->rows_base + (int) $count;
			$this->write();
		}

		// called back by the Output class as the dump is written to disk
		public function setBytes( $bytes ) {
			$this->data['bytes'] = (int) $bytes;
			$this->write();
		}

		public function isFinished() {
			return $this->data['state'] != 'running';
		}

		public function finish( $state, $message, $extra = array() ) {
			$this->data['state'] = $state;			// 'done' or 'error'
			$this->data['message'] = $message;
			foreach( $extra as $key => $value )
				$this->data[$key] = $value;
			$this->write( true );
		}

		public function getData() {
			return $this->data;
		}

		private function write( $force = false ) {
			$now = microtime(true);
			if ( !$force && ($now - $this->last_write) < $this->write_interval )
				return;

			$this->last_write = $now;
			$this->data['updated'] = $now;

			$json = json_encode( $this->data );
			// write to a scratch file first, so a poll never sees a half written state
			$tmp = $this->path . '.' . getmypid() . '.tmp';
			if ( @file_put_contents( $tmp, $json ) !== false && @rename( $tmp, $this->path ) )
				return;

			@unlink( $tmp );
			@file_put_contents( $this->path, $json, LOCK_EX );
		}
	}
}
?>
