<?php
/**
 * This file is a part of MyWebSQL package
 * outputs scripts and stylesheets for the application
 * @file:      cache.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */
	$useCache = file_exists('js/min/minify.txt');
	include('modules/configuration.php');
	initConfiguration(false);

	$fileList = v($_REQUEST["script"]);
	// concat theme path to make etags unique per theme
	if ($fileList == '')	$fileList = THEME_PATH . v($_REQUEST["css"]);
	if ($fileList == '')	exit();
	
	// cache scripts and css per version, if not in development mode
	if ($useCache) {
		$versionTag = md5($fileList.APP_VERSION);
		$eTag = v($_SERVER['HTTP_IF_NONE_MATCH']);
		if ($eTag != '' && $versionTag == $eTag) {   
			header($_SERVER['SERVER_PROTOCOL'].' 304 Not Modified');
			header('Content-Length: 0');
			exit();
		}
		header('Etag: '.$versionTag);
	}

	include("lib/functions.php");

	buffering_start();
	
	$regex = '#^(\w+/){0,2}\w+$#';

	if (v($_REQUEST["script"]) != "")
	{
		$script_path = $useCache ? "js/min" : "js";
		$scripts = explode(",", $_REQUEST["script"]);
		header("mime-type: text/javascript");
		header("content-type: text/javascript");
		echo "/**\n * This file is a part of MyWebSQL package\n * @web        http://mywebsql.net\n * @license    http://mywebsql.net/license\n */\n\n";
		foreach($scripts as $script)
			if ( preg_match($regex, $script) == 1 )
				if(file_exists("$script_path/$script".".js"))
					echo file_get_contents("$script_path/$script".".js") . "\n\n";
	}
	else if (v($_REQUEST["css"]) != "")
	{
		$styles = explode(",", $_REQUEST["css"]);
		header("mime-type: text/css");
		header("content-type: text/css");
		echo "/**\n * This file is a part of MyWebSQL package\n * @web        http://mywebsql.net\n * @license    http://mywebsql.net/license\n */\n\n";
		foreach($styles as $css)
			if ( preg_match($regex, $css) == 1 )
				if(file_exists("themes/".THEME_PATH."/$css".".css"))
					echo file_get_contents("themes/".THEME_PATH."/$css".".css") . "\n\n";
	}

	buffering_flush();
?>