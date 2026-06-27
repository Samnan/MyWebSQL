<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/infovars.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        https://github.com/Samnan/MyWebSQL
 * @license    https://github.com/Samnan/MyWebSQL/license
 */

	function processRequest(&$db) {
		if ($db->queryVariables()) {
			createSimpleGrid($db, __('Server Variables'));
		}
	}

?>