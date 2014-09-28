<?php
/**
 * This file is a part of MyWebSQL package
 * Core functions for server side database related functionality
 *
 * @file:      lib/functions.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function __($text) {
		if (LANGUAGE == 'en') return $text;
		include(BASE_PATH . '/lang/'.LANGUAGE.'.php');
		return ( isset($LANGUAGE[$text]) ? $LANGUAGE[$text] : $text );
	}

	function getGeneratedJS() {
		if (LANGUAGE == 'en') return '';

		$script = '<script language="javascript" type="text/javascript">'."\nwindow.lang = {\n";
		include(BASE_PATH . '/lang/'.LANGUAGE.'.php');
		foreach($LANGUAGE_JS as $key=>$txt)
			$script .= '"'.htmlspecialchars($key).'":"'.htmlspecialchars($txt)."\",\n";

		$script .= "\"MyWebSQL\":\"MyWebSQL\"\n};\n</script>\n";

		return $script;
	}

	function log_message($str) {
		if (defined('LOG_MESSAGES') && LOG_MESSAGES)
			error_log($str);
	}

	function matchFileHeader(&$str, $hdr) {
		if (is_array($hdr)) {
			foreach($hdr as $v) {
				if ( substr($str, 0, strlen($v)) == $v)
					return true;
			}
		}
		else
			return  ( substr($str, 0, strlen($hdr)) == $hdr);

		return false;
	}

	function bytes_value($val) {
		$val = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}

	/* the reverse of bytes_value */
	function format_bytes($val) {
		if ($val < 1024)
			return $val.' B';
		$size = ($val < 1024*1024) ? number_format($val/1024).' KB' : number_format($val/(1024*1024), 2).' MB';
		return $size;
	}

	// enable encryption on login page only if we are not using HTTPS
	function secureLoginPage() {
		return (defined('SECURE_LOGIN') && SECURE_LOGIN==TRUE && v($_SERVER["HTTPS"]) == '') ? TRUE : FALSE;
	}

	function find_view($name) {
		// if a list of views is provided, load the one that is found first
		if (is_array($name)) {
			foreach($name as $view_name) {
				if (file_exists(BASE_PATH . '/modules/views/' . $view_name . '.php')) {
					return (BASE_PATH . '/modules/views/' . $view_name . '.php');
				}
			}
		}
		return (BASE_PATH . '/modules/views/' . $name . '.php');
	}
	function view($name, $replace = array(), $data = NULL) {
		ob_start();

		include( find_view($name) );

		$file = ob_get_clean();
		if (count($replace) > 0) {
			$find = array();
			foreach($replace as $key => $value)
				$find[] = '{{' . $key . '}}';
			$replace = array_values($replace);
			return str_replace($find, $replace, $file);
		}
		return $file;
	}

	function getLanguageList() {
		include (BASE_PATH . "/config/lang.php");
		$langList = array();
		$files = scandir(BASE_PATH . "/lang/");
		foreach ($files as $lang) {
			if (substr($lang, -4) == '.php') {
				$langList[substr($lang, -6, 2)] = '';
			}
		}
		// keeping english as the first choice always
		$langList = array_intersect_key($_LANGUAGES, $langList);
		return $langList;
	}

	function getServerList() {
		if ( AUTH_TYPE != 'LOGIN' ) {
			if ( AUTH_TYPE != 'CUSTOM' )
				return false;
			require_once(BASE_PATH . '/lib/auth/custom.php');
			if ( !MyWebSQL_Auth_Custom::showServerList() )
				return false;
		}

		include (BASE_PATH . "/config/servers.php");
		if (!defined('ALLOW_CUSTOM_SERVERS'))
			define('ALLOW_CUSTOM_SERVERS', $ALLOW_CUSTOM_SERVERS);
		if (!defined('ALLOW_CUSTOM_SERVER_TYPES'))
			define('ALLOW_CUSTOM_SERVER_TYPES', $ALLOW_CUSTOM_SERVER_TYPES);
		return $SERVER_LIST;
	}

	function create_module_id( $mod ) {
		return uniqid($mod);
	}

	function curl_get($url, array $get = array(), $options = array())
	{
		if (!function_exists('curl_exec'))
			return '';

	    $defaults = array(
			CURLOPT_URL => $url . (strpos($url, '?') === FALSE ? '?' : '') . http_build_query($get),
			CURLOPT_HEADER => 0,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_TIMEOUT => 4
	    );

	    $ch = curl_init();
	    curl_setopt_array($ch, ($options + $defaults));
	    if( ! $result = curl_exec($ch))
	    {
	        //trigger_error(curl_error($ch));
	        return '';
	    }
	    curl_close($ch);
	    return $result;
	}

	function isAjaxRequest() {
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
			return true;
		return false;
	}

	// creates a resultant array with a defined list of values from input array
	function extract_vars(&$arr, $keys) {
		$result = $keys;
		foreach($keys as $key => $value) {
			if (isset($arr[$key]))
				$result[$key] = $arr[$key];
		}
		return $result;
	}

	function phpCheck( $ver ) {
		return version_compare(PHP_VERSION, $ver, '>=');
	}
	
	function get_backup_filename( $compression, $filename ) {
		include_once(BASE_PATH . "/config/backups.php");
		$file = '';
		$search = array(
			'<db>',
			'<date>',
			'<ext>'
		);
		$replace = array(
			Session::get('db', 'name'),
			date( BACKUP_DATE_FORMAT ),
			'.sql'
		);

		$file .= str_replace( $search, $replace, $filename );

		if ( $compression != '' )
			$file .= $compression == 'bz' ? '.bz2' : '.gz';

		// verify that the filename is valid
		$matches = '[]/\\\?\*:<>|"\'';
		if ( strpbrk($file, $matches) )
			return false;
		
		return  BACKUP_FOLDER . $file;
	}
?>