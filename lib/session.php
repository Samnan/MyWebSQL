<?php
/**
 * This file is a part of MyWebSQL package
 * session management library
 *
 * @file:      lib/session.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        https://github.com/Samnan/MyWebSQL
 * @license    https://github.com/Samnan/MyWebSQL/license
 */

if (!defined("CLASS_SESSMANAGER_INCLUDED"))
{
	define("CLASS_SESSMANAGER_INCLUDED", "1");
	include_once('external/rijndael.php'); // for encryption

	class Session {
		static $encObj;

		static function init() {
			session_start();
			self::$encObj = new Crypt_Rijndael();
			$key = defined('MYWEBSQL_SESSION_KEY') ? MYWEBSQL_SESSION_KEY : '';
			if (!is_string($key) || strlen($key) != 64 || !preg_match('/^[a-fA-F0-9]{64}$/', $key)) {
				throw new RuntimeException(
					'MYWEBSQL_SESSION_KEY must be set to a 64-character hexadecimal string'
				);
			}
			self::$encObj->setKey(hex2bin($key));
		}

		static function set($section, $name, $value, $encrypt=false) {
			$_SESSION[$section][$name] = ($encrypt ? self::$encObj->encrypt($value) : $value);
		}

		// add a new item to array of values, without modifying the other values
		static function add($section, $name, $value) {
			$_SESSION[$section][$name][] = $value;
		}

		static function get_all($section) {
			$var = isset($_SESSION[$section]) ? $_SESSION[$section] : array();
			return $var;
		}

		static function get($section, $name, $encrypt=false) {
			$var = isset($_SESSION[$section][$name]) ? $_SESSION[$section][$name] : '';
			return ($encrypt ? self::$encObj->decrypt($var) : $var);
		}

		static function del($section, $name='') {
			if ($name == '')
				unset($_SESSION[$section]);
			else
				unset($_SESSION[$section][$name]);
		}

		static function set_pref($section, $name, $value) {
			$_SESSION['pref_'.$section][$name] = $value;
		}

		static function get_pref($section, $name) {
			return v($_SESSION['pref_'.$section][$name]);
		}

		static function destroy() {
			unset($_SESSION['auth']);
			unset($_SESSION['db']);
			session_destroy();
		}

		static function close() {
			session_write_close();
		}
	}
}
?>