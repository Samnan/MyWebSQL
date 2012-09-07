<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/splash.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function getSplashScreen($msg = '', $formCode='') {
		if ($formCode) {
			$formCode = '<div class="login"><form method="post" action="" name="dbform" id="dbform" style="text-align:center">'
							. $formCode . '</form></div>';
		}

		$scripts = "jquery";
		$extraScript = "";
		
		if (secureLoginPage()) {
			$scripts = "jquery,encrypt";
			$e = Session::get('auth_enc', 'e');
			$d = Session::get('auth_enc', 'd');
			$n = Session::get('auth_enc', 'n');
			$keyLength = 128;
			if(!$e || !$d || !$n) {
				$enc_lib = BASE_PATH . ((extension_loaded('openssl') && extension_loaded('gmp') && extension_loaded('bcmath'))
					? "/lib/external/jcryption.php"
					: "/lib/external/jcryption-legacy.php");
				require_once( $enc_lib );
				$jCryption = new jCryption();
				$keys = $jCryption->generateKeypair($keyLength);
				$e = array("int" => $keys["e"], "hex" => $jCryption->dec2string($keys["e"],16));
				$d = array("int" => $keys["d"], "hex" => $jCryption->dec2string($keys["d"],16));
				$n = array("int" => $keys["n"], "hex" => $jCryption->dec2string($keys["n"],16));
				Session::set('auth_enc', 'e', $e);
				Session::set('auth_enc', 'd', $d);
				Session::set('auth_enc', 'n', $n );
			}
			$keyData = '{"e":"'.$e["hex"].'","n":"'.$n["hex"].'","maxdigits":"'.intval($keyLength*2/16+3).'"}';
			$extraScript = '<script language="javascript" type="text/javascript">
									$(function() {
										$.jCryption.defaultOptions.getKeysURL = '.$keyData.';
										$("#dbform").jCryption();
									});
								</script>';
		}

		$replace = array(
			'MESSAGE' => $msg ? '<div class="msg">'.htmlspecialchars($msg).'</div>' : '',
			'FORM'    => $formCode,
			'APP_VERSION'  => APP_VERSION,
			'PROJECT_SITEURL' => PROJECT_SITEURL,
			'SCRIPTS' => $scripts,
			'EXTRA_SCRIPT' => $extraScript
			);

		return view('splash', $replace);
	}
?>