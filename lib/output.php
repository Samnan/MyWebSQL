<?php
/**
 * This file is a part of MyWebSQL package
 * output/buffering management library
 * when used as an object, the output can be redirected to a file
 *
 * @file:      lib/output.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        https://github.com/Samnan/MyWebSQL
 * @license    https://github.com/Samnan/MyWebSQL/license
 */

if (!defined("CLASS_OUTPUT_INCLUDED"))
{
	define("CLASS_OUTPUT_INCLUDED", "1");

	class Output {
		// size of the buffer that is written out to the file in one go
		const CHUNK_SIZE = 65536;

		public $file;
		public $compression;
		public $file_handle;
		public $bytes = 0;			// bytes handed over to the file so far
		public $progress = null;	// optional object with a setBytes() method
		private $buffering = false;

		// controls output buffering
		public static function buffer() {
			function_exists('ob_gzhandler') && ( !ini_get( 'zlib.output_compression') )
				? ob_start("ob_gzhandler") : ob_start();
			ob_implicit_flush(0);
			// if a module cleans the buffer, then starts buffering again, this will avoid php notices
			if (!defined('OUTPUT_BUFFERING'))
				define('OUTPUT_BUFFERING', true);
		}

		// flushes output buffer as required
		public static function flush() {
			if (!defined('OUTPUT_BUFFERING'))
				return true;

			if ( ini_get( 'zlib.output_compression') || function_exists('ob_gzhandler') ) {
				ob_end_flush();
				return true;
			}

			$HTTP_ACCEPT_ENCODING = $_SERVER["HTTP_ACCEPT_ENCODING"];
			if( headers_sent() )
				$encoding = false;
			else if( strpos($HTTP_ACCEPT_ENCODING, 'x-gzip') !== false )
				$encoding = 'x-gzip';
			else if( strpos($HTTP_ACCEPT_ENCODING,'gzip') !== false )
				$encoding = 'gzip';
			else
				$encoding = false;

			if( $encoding && function_exists("gzcompress") ) {
				$contents = ob_get_clean();
				$_len = strlen($contents);
				if ($_len < 2048)		// no need to waste time in compressing very little data
					print($contents);
				else {
					header('Content-Encoding: '.$encoding);
					print("\x1f\x8b\x08\x00\x00\x00\x00\x00");
					$contents = gzcompress($contents, 9);
					print($contents);
				}
			}
			else
				ob_end_flush();
		}

		public function __construct( $file, $compression = false ) {
			$this->file = $file;
			$this->compression = $compression;

			if ($compression == 'gz') {
				$this->file_handle = gzopen( $file, 'w' );
			}
			else if ($compression == 'bz') {
				$this->file_handle = bzopen( $file, 'w' );
			} else {
				$this->file_handle = fopen( $file, 'wb' );
			}

			// nothing to redirect the output to, leave the buffering alone so that the
			// caller can still report the failure to the browser
			if ( !$this->file_handle )
				return;

			// flush every CHUNK_SIZE bytes instead of holding the whole dump in memory
			ob_start( array( $this, 'output_callback' ), self::CHUNK_SIZE );
			$this->buffering = true;
		}

		public function __destruct() {
			$this->end();
		}

		public function is_valid() {
			return $this->file_handle != false;
		}

		// only works if output is being redirected with compression
		public function end() {
			// only close the buffer we started ourselves, end() is also called by the destructor
			if ( $this->buffering ) {
				@ob_end_flush();
				$this->buffering = false;
			}

			if ( $this->file_handle ) {
				if ( $this->compression == 'gz' ) {
					gzclose( $this->file_handle );
				}
				else if ( $this->compression == 'bz' ) {
					bzclose( $this->file_handle );
				} else {
					fclose( $this->file_handle );
				}
				$this->file_handle = null;
				return true;
			}
		}

		public function output_callback( $buffer ) {
			if ( $this->compression == 'gz' ) {
				gzwrite( $this->file_handle, $buffer );
			}else if ( $this->compression == 'bz' ) {
				bzwrite( $this->file_handle, $buffer );
			} else {
				fwrite( $this->file_handle, $buffer );
			}

			$this->bytes += strlen( $buffer );
			if ( $this->progress )
				$this->progress->setBytes( $this->bytes );

			return '';	// nothing of this goes to the browser
		}
	}
}
?>