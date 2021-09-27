<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      lib/auth/custom.sample.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */
	
	/* to implement custom auth, use the following sample to get a head start - see online docs for more info */
	class MyWebSQL_Auth_Custom {
		private $error;

		/* implement main authentication mechanism here */
		public function authenticate($userid, $password, $server) {
			/*
			 * $server = array(0 => <<display name of server>>, 1 => <<Server information array>>)
			 */
			$wordpress_auth = Session::get('auth', 'wordpress_auth', true);
			if ($wordpress_auth) {
				if ($wordpress_auth = @unserialize($wordpress_auth)) {
					Session::set('auth', 'server_name', $wordpress_auth['server_name'], true);
					Session::set('auth', 'host', $wordpress_auth['host'], true);
					Session::set('db', 'driver', $wordpress_auth['driver']);
					Session::set('auth', 'user', $wordpress_auth['user'], true);
					Session::set('auth', 'pwd', $wordpress_auth['pwd'], true);
					Session::set('db', 'name', $wordpress_auth['dbname']);
					Session::set('auth', 'valid', true);
					Session::del('auth', 'wordpress_auth');
					//header('Location: '.EXTERNAL_PATH);
					return true;
				}
			}
			
			Session::destroy();
			$this->setError(__('Invalid Credentials'));
			return false;
		}

		/* return true or false from this function to show or hide the server list on login page */
		public static function showServerList() {
			return false;
		}

		/* this function should return the basic auth parameters if auth is successful */
		public function getParameters() {
			$param = array(
				'host' => Session::get('auth', 'host', true),
				'driver' => Session::get('db', 'driver'),
				'username' => Session::get('auth', 'user', true),
				'password' => Session::get('auth', 'pwd', true)
			);

			return $param;
		}
		
		/* leave the following functions unchanged */
		
		public function getError() {
			return $this->error;
		}

		private function setError($str) {
			$this->error = $str;
			return false;
		}
	}
?>