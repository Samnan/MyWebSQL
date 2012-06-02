<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      lib/auth/auth.php
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
			
			// CHANGE THE FOLLOWING FOR PROPER AUTHENTICATION BEHAVIOUR */
			if ($userid == 'mywebsql' && $password == 'custom')	{
				
				// the following is required for proper functionality after authentication
				Session::set('auth', 'valid', true);
				Session::set('auth', 'server_name', $server[0], true);
				Session::set('auth', 'host', $server[1]['host'], true);
				Session::set('db', 'driver', $server[1]['driver']);
				Session::set('auth', 'user', $userid, true);
				Session::set('auth', 'pwd', $password, true);

				header('Location: '.EXTERNAL_PATH);
				return true;
			} else {
				$this->setError(__('Invalid Credentials'));
			}
			
			return false;
		}

		/* return true or false from this function to show or hide the server list on login page */
		public static function showServerList() {
			return true;
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