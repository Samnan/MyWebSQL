<?php

	define('BASE_PATH', dirname(__FILE__));

	if (version_compare(PHP_VERSION, '5.3', '<')) {
		clearstatcache();
	}
	else {
		clearstatcache(TRUE);
	}
	include(BASE_PATH . '/config/lang.php');  // we have to include language first for proper settings
	if (isset($_REQUEST["lang"]) && array_key_exists($_REQUEST["lang"], $_LANGUAGES) && file_exists(BASE_PATH . '/lang/'.$_REQUEST["lang"].'.php')) {
		define('LANGUAGE', $_REQUEST["lang"]);
	}
	else if (isset($_COOKIE["lang"]) && array_key_exists($_COOKIE["lang"], $_LANGUAGES) && file_exists(BASE_PATH . '/lang/'.$_COOKIE["lang"].'.php'))
		define('LANGUAGE', $_COOKIE["lang"]);
	else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		$_user_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		if (array_key_exists($_user_lang, $_LANGUAGES) && file_exists(BASE_PATH . '/lang/'.$_user_lang.'.php'))
			define('LANGUAGE', $_user_lang);
		unset($_user_lang);
	}
	require_once(BASE_PATH . '/lib/functions.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>MyWebSQL Installation</title>

	<style type="text/css">
	body { width: 42em; margin: 0 auto; font-family: sans-serif; background: #fff; font-size: 1em; }
	h1 { }
	h1 a { text-decoration: none; margin: 10px 20px 10px 10px; }
	h1 span { float: right; font-family: arial; font-size: 12px; font-weight: normal; line-height: 50px; }
	h1 + p { margin: 0 0 2em; color: #333; font-size: 90%; font-style: italic; }
	code { font-family: monaco, monospace; }
	table { border-collapse: collapse; width: 100%; }
		table th,
		table td { padding: 0.4em; text-align: left; vertical-align: top; }
		table th { width: 12em; font-weight: normal; }
		table tr:nth-child(odd) { background: #eee; }
		table td.pass { color: #191; }
		table td.fail { color: #911; }
	#results { padding: 0.8em; color: #fff; font-size: 1.2em; }
	#results.pass { background: #191; }
	#results.fail { background: #911; }
	</style>

</head>
<body>

	<h1><a href="http://mywebsql.net" target="_blank"><img width="45" height="38" border="0" alt="MyWebSQL" class="logo" src="img/logo.png"></a>Environment Tests
	<span>
		<!--label><?php echo __('Language'); ?>:&nbsp;&nbsp;</label><select name="lang" onchange="window.location.search='lang='+this.value">
		<?php $langList = getLanguageList();
			$selLang = LANGUAGE;
			foreach($langList as $lang => $name) {
				if ($selLang == $lang)
					echo '<option value="'.$lang.'" selected="selected">'.$name.'</option>';
				else
					echo '<option value="'.$lang.'">'.$name.'</option>';
			}
		?>
		</select-->
	</span>
	</h1>

	<p>
		The following tests have been run to determine if <a href="http://mywebsql.net">MyWebSQL</a> will work in your environment.<br />
		If any of the tests have failed, consult the <a href="http://mywebsql.net/docs"> online documentation</a>
		for more information on how to correct the problem.
	</p>

	<?php
		$failed = false;
		$php_version = false;
		$openssl = false;
		$bcmath = false;
		$gmp = false;
		$uri = false;
	?>

	<table cellspacing="0">
		<tr>
			<th>PHP Version</th>
			<?php if (phpCheck(5.0)): $php_version = true; ?>
				<td class="pass"><?php echo PHP_VERSION ?></td>
			<?php else: $failed = TRUE ?>
				<td class="fail">MyWebSQL requires PHP 5.0 or newer, your current PHP version is <?php echo PHP_VERSION ?>.</td>
			<?php endif ?>
		</tr>
		<tr>
		<!--tr>
			<th>Temp Directory</th>
			<php if (is_dir('./tmp') AND is_writable('./tmp')): >
				<td class="pass"><?php echo './tmp/' ?></td>
			<php else: $failed = TRUE >
				<td class="fail">The <code><?php echo './tmp/' ?></code> directory is not writable.</td>
			<php endif >
		</tr-->
		<tr>
			<th>Math Library</th>
			<?php if (extension_loaded('bcmath')): $bcmath = true; ?>
				<td class="pass">Pass</td>
			<?php else: $failed = TRUE ?>
				<td class="fail"><?php echo str_replace('{{NAME}}', '<a href="http://php.net/bcmath">bcmath</a>', '{{NAME}} extension is not available'); ?></td>
			<?php endif ?>
		</tr>
		<tr>
			<th>OpenSSL Library</th>
			<?php if (extension_loaded('openssl')): $openssl = true; ?>
				<td class="pass">Pass</td>
			<?php else: $failed = TRUE ?>
				<td class="fail"><?php echo str_replace('{{NAME}}', '<a href="http://php.net/openssl">openssl</a>', '{{NAME}} extension is not available'); ?></td>
			<?php endif ?>
		</tr>
		<tr>
			<th>Precision Library</th>
			<?php if (extension_loaded('gmp')): $gmp = true; ?>
				<td class="pass">Pass</td>
			<?php else: $failed = TRUE ?>
				<td class="fail"><?php echo str_replace('{{NAME}}', '<a href="http://php.net/gmp">gmp</a>', '{{NAME}} extension is not available'); ?></td>
			<?php endif ?>
		</tr>
		<tr>
			<th>URI Determination</th>
			<?php if (isset($_SERVER['REQUEST_URI']) OR isset($_SERVER['PHP_SELF']) OR isset($_SERVER['PATH_INFO'])): $uri = true; ?>
				<td class="pass">Pass</td>
			<?php else: $failed = TRUE ?>
				<td class="fail">Neither <code>$_SERVER['REQUEST_URI']</code>, <code>$_SERVER['PHP_SELF']</code>, or <code>$_SERVER['PATH_INFO']</code> is available.</td>
			<?php endif ?>
		</tr>
		
		<tr>
			<th>Database Connectivity</th>
			<?php if ( (function_exists('mysql_connect') ||
				function_exists('mysqli_connect') ||
				function_exists('pg_connect') ||
				function_exists('sqlite_open')
				)): ?>
				<td class="pass">Pass</td>
			<?php else: $failed = TRUE ?>
				<td class="fail">None of the required database client libraries are installed. You will not be able to use MyWebSQL unless you install one or more of these client libraries.</td>
			<?php endif ?>
		</tr>
	</table>

	<?php if ($failed === TRUE): ?>
		<p id="results" class="fail">✘ MyWebSQL may not work correctly with your environment.</p>
	<?php else: ?>
		<p id="results" class="pass">✔ Your environment passed all requirements.<br />
			Please remove or rename the <code>install.php</code> file now.</p>
	<?php endif ?>

	<h1>Database Libraries</h1>

	<p>
		At least one of the following is required to use MyWebSQL.
	</p>

	<table cellspacing="0">
		<tr>
			<th>MySQL Client Library</th>
			<?php if (function_exists('mysql_connect')): ?>
				<td class="pass">Pass</td>
			<?php else: ?>
				<td class="fail"><a href="http://php.net/mysql">MySQL</a> client library is not installed.</td>
			<?php endif ?>
		</tr>
		<tr>
			<th>MySQL improved functionality</th>
			<?php if (function_exists('mysqli_connect')): ?>
				<td class="pass">Pass</td>
			<?php else: ?>
				<td class="fail"><a href="http://php.net/mysqli">MySQL Improved</a> client library is not installed.</td>
			<?php endif ?>
		</tr>
		<tr>
			<th>PostgreSQL Client Library</th>
			<?php if (function_exists('pg_connect')): ?>
				<td class="pass">Pass</td>
			<?php else: ?>
				<td class="fail"><a href="http://php.net/pgsql">PostgreSQL</a> client library is not installed.</td>
			<?php endif ?>
		</tr>
		<tr>
			<th>SQLite Client Library</th>
			<?php if (function_exists('sqlite_open')): ?>
				<td class="pass">Pass</td>
			<?php else: ?>
				<td class="fail"><a href="http://php.net/sqlite">SQLite</a> client library is not installed.</td>
			<?php endif ?>
		</tr>
	</table>

	<h1>Optional Tests</h1>

	<p>
		The following extensions are not required to run MyWebSQL, but if enabled, they can provide additional functionality.
	</p>

	<table cellspacing="0">
		<tr>
			<th>cURL Enabled</th>
			<?php if (extension_loaded('curl')): ?>
				<td class="pass">Pass</td>
			<?php else: ?>
				<td class="fail">MyWebSQL uses the <a href="http://php.net/curl">cURL</a> extension for checking new versions every week.</td>
			<?php endif ?>
		</tr>
	</table>

</body>
</html>
