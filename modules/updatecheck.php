<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/updatecheck.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        https://github.com/Samnan/MyWebSQL
 * @license    https://github.com/Samnan/MyWebSQL/license
 */

	// returns json output for online update check
	function processRequest(&$db) {
		ob_end_clean();
		include_once(BASE_PATH . "/lib/output.php");
		Output::buffer();
		$url = "https://api.github.com/repos/Samnan/MyWebSQL/releases/latest";

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				'User-Agent: MyWebSQL/' . APP_VERSION, // Required by GitHub
				'Accept: application/vnd.github+json',
			],
		]);

		$response = curl_exec($ch);
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($status !== 200) {
			echo 'Could not connect to GitHub repo for update check.';
			exit;
		}

		$data = json_decode($response, true);
		$version = $data['tag_name'] ?? '';
		if(!$version) {
			echo 'Could not find current version for latest release from GitHub repo.';
			exit;
		}

		$version = substr($version, 1);

		Session::set('updates', 'check', '1');

		$versionInfo = [
			'success' => 1,
			'available' => (float) $version > (float) APP_VERSION,
			'link' => 'https://github.com/Samnan/MyWebSQL/releases/latest'
		];

		echo(json_encode($versionInfo));
		$db->disconnect();
		Output::flush();
		exit();
	}
?>