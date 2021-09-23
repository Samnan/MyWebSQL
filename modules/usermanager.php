<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/usermanager.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		$action = v($_REQUEST["id"]);
		include(BASE_PATH . "/lib/usermanager.php");
		$legacyServer = Session::get('db', 'version') < 5;
		$editor = new userManager($db, $legacyServer);
		$message = '';

		if ($action != '') {
			if ($action == "add")
				$result = addUser($db, v($_REQUEST["query"]), $editor);
			else if ($action == "delete")
				$result = deleteUser($db, v($_REQUEST["query"]), $editor);
			else if ($action == "update")
				$result = updateUser($db, v($_REQUEST["query"]), $editor);

			if ($result) {
				$db->flush('PRIVILEGES', true);
				$message = __('The command executed successfully');
			}
			else
				$message = __('Error occurred while executing the query');
		}

		displayUserForm($db, $editor, $message, $action);
	}

	function displayUserForm(&$db, &$editor, $message, $action) {
		$dbList = $db->getDatabases();
		$userList = $editor->getUsersList();
		$privilegeNames = Privileges::getNames();
		$dbPrivilegeNames = DbPrivileges::getNames();

		// current user name is not plaintext in case of 'update' action
		$userName = '';
		if ($action == 'update') {
			$obj = json_decode(v($_REQUEST['query']));
			if (is_object($obj))
				$userName = $obj->username . '@' . $obj->hostname;
		} else
			$userName = v($_REQUEST['query']);

		$currentUser = selectUser($userList, $userName);
		$privileges  = array();
		$dbPrivileges = array();
		$userInfo = array();
		if ($currentUser) {
			$privileges = $currentUser->getGlobalPrivileges();
			foreach($dbList as $db_name)
				$dbPrivileges[$db_name] = $currentUser->getDbPrivileges($db_name);

			$userInfo = array('username' => $currentUser->userName, 'host' => $currentUser->host);
		}
		$users = userOptions($userList, $currentUser);

		$replace = array(
			'ID' => v($_REQUEST["id"]) ? htmlspecialchars($_REQUEST["id"]) : '',
			'MESSAGE' => $message,
			'USERS' => $users,
			'USER_INFO' => json_encode($userInfo),
			'DATABASES' => json_encode($dbList),
			'PRIVILEGES' => json_encode($privileges),
			'DB_PRIVILEGES' => json_encode($dbPrivileges),
			'PRIVILEGE_NAMES' => json_encode($privilegeNames),
			'DB_PRIVILEGE_NAMES' => json_encode($dbPrivilegeNames)
		);
		echo view('usermanager', $replace);
	}

	function selectUser($list, $user) {
		foreach($list as $obj) {
			$name = $obj->userName . '@' . $obj->host;
			if ($user == $name)
				return $obj;
		}

		$obj = count($list) > 0 ? $list[0] : NULL;
		return $obj;
	}

	function addUser(&$db, $info, &$editor) {
		$info = json_decode($info);

		if (!is_object($info))
			return false;

		return $editor->add($info->username, $info->hostname, $info->pwd, v($info->native));
	}

	function deleteUser(&$db, $info, &$editor) {
		$info = json_decode($info);

		if (!is_object($info))
			return false;

		return $editor->delete($info->username, $info->host);
	}

	function updateUser(&$db, $info, &$editor) {
		$info = json_decode($info);
		if (!is_object($info))
			return false;

		// only change user info if it requires update
		if ($info->oldusername != $info->username || $info->oldhostname != $info->hostname) {
			$result = $editor->update($info->oldusername, $info->oldhostname, $info->username, $info->hostname);
			if (!$result)
				return false;
		}

		// change password only if requested
		if (isset($info->password) && $info->password != '') {
			$result = $editor->updatePassword($info->username, $info->hostname, $info->password, v($info->native));
			if (!$result)
				return false;
		} else if (isset($info->removepass) && $info->removepass == '1') {
			$result = $editor->updatePassword($info->username, $info->hostname, '');
			if (!$result)
				return false;
		}

		$user = $editor->getUser($info->username, $info->hostname);
		$user->setGlobalPrivileges($info->privileges);

		$dbList = $db->getDatabases();
		foreach($dbList as $db_name) {
			$result = $user->setDbPrivileges($db_name, isset($info->db_privileges->$db_name) ? $info->db_privileges->$db_name : array() );
			if (!$result)
				return false;
		}

		return true;
	}

	function userOptions($array, $selected) {
		$str = $selected == '' ? '<option value="">- - -</option>' : '';
		foreach($array as $user) {
			$name = $user->userName . '@' . $user->host;
			if ($selected->userName == $user->userName && $selected->host == $user->host)
				$str .= '<option selected="selected" value="'.htmlspecialchars($name).'">'.htmlspecialchars($name).'</option>';
			else
				$str .= '<option value="'.htmlspecialchars($name).'">'.htmlspecialchars($name).'</option>';
		}

		return $str;
	}
?>