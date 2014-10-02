<?php
/**
 * This file is a part of MyWebSQL package
 * output/buffering management library
 * when used as an object, the output can be redirected to a file
 *
 * @file:      lib/output.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (!defined("CLASS_OUTPUT_INCLUDED"))
{
	define("CLASS_OUTPUT_INCLUDED", "1");

	class Output {
		public $file;
		public $compression;
		public $file_handle;

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
			ob_start( array( $this, 'output_callback' ) );
		}

		public function __destruct() {
			$this->end();
		}

		public function is_valid() {
			return $this->file_handle != false;
		}

		// only works if output is being redirected with compression
		public function end() {
			@ob_end_flush();
			if ( $this->file_handle ) {
				if ( $this->compression == 'gz' ) {
					gzclose( $this->file_handle );
				}
				if ( $this->compression == 'bz' ) {
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
		}
	}
}
?>