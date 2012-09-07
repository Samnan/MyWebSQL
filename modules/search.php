<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/search.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		if (isset($_POST['keyword']) && isset($_POST['operator']) && is_array(v($_POST['tables'])) ) {
			searchDatabase($db);
		} else {
			$db_tables = $db->getTables();
			$replace = array('TABLELIST' => json_encode($db_tables),
				);
			echo view('search', $replace);
		}
	}
	
	function searchDatabase(&$db) {
		$operator = v($_POST['operator']);
		$fieldTypes = array();
		$fieldTypes['numeric'] = v($_POST['ftype_num']) == 'on';
		$fieldTypes['char'] = v($_POST['ftype_char']) == 'on' && !$fieldTypes['numeric'];
		$fieldTypes['text'] = v($_POST['ftype_text']) == 'on' && !$fieldTypes['numeric'];
		$fieldTypes['date'] = v($_POST['ftype_date']) == 'on' && !$fieldTypes['numeric'];

		include(BASE_PATH . "/lib/tablesearch.php");
		$searchTool = new tableSearch($db);
		$searchTool->setTables(v($_POST['tables']));
		$searchTool->setText(v($_POST['keyword']));
		$searchTool->setOperator($operator);
		$searchTool->setFieldTypes($fieldTypes);
		
		$data = array('results' => array(), 'queries' => array());
		if ($searchTool->search()) {
			$data['results'] = $searchTool->getResults();
			$data['queries'] = $searchTool->getQueries();
		}
		
		$message = str_replace('{{KEYWORD}}', "&quot;" . htmlspecialchars($_POST['keyword']) . "&quot;", __('Search results for {{KEYWORD}} in the database'));
		$replace = array('MESSAGE' => $message);
		echo view('search_results', $replace, $data);
	}

?>