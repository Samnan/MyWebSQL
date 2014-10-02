<?php
/**
 * This file is a part of MyWebSQL package
 * user defined options management library
 *
 * @file:      lib/options.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (!defined("CLASS_OPTIONS_INCLUDED"))
{
	define("CLASS_OPTIONS_INCLUDED", "1");

	class Options {
		static function set($name, $value) {
			$_COOKIE[$name] = $value;
		}

		static function get($name, $default = '') {
			$var = isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default;
			return $var;
		}
	}
}
?>