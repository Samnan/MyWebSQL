<?php
/**
 * This file is a part of MyWebSQL package
 * Utility functions for server side database related functionality
 *
 * @file:      lib/util.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	include(BASE_PATH . '/lib/functions.php');
	define('LIMIT_REGEXP', '/(.*)[\s]+(limit[\s]+[\d]+([\s]*(,|offset)[\s]*[\d]+)?)$/is');
	define('SORT_REGEXP', '/(.*)[\s]+(ORDER[\s]+BY[\s]+([a-zA-z0-9\._]+|`.*`|\'.*\'|".*")\s*(ASC|DESC)?(\s*\,\s*([a-zA-z0-9\._]+|`.*`|\'.*\'|".*")\s*(ASC|DESC)?)*)$/is');

	function showDBError() {
		return __('Database connection failed to the server') . '. ' . __('Host') . ': ' . DB_HOST . ', ' . __('User') . ': ' . DB_USER;
	}

	function getDbName() {
		return Session::get('db', 'name');
	}

	function getDbList(&$db) {
		include (BASE_PATH . "/config/database.php");
		$server = Session::get('auth', 'server_name', true);
		// return restrictive db list based on config
		if (isset($DB_LIST) && isset($DB_LIST[$server]))
			return $DB_LIST[$server];
		// return default db list user has access to
		return $db->getDatabases();
	}

	function printDbList(&$db) {
		$dblist = getDbList($db);

		// if there is one database for the given userid, let's select it to cut out one extra step
		if (! Session::get('db', 'name') ) {
			$stDb = 0; $selDb = "";
			$stDbList = $db->getStandardDbList();
			foreach($dblist as $dbname) {
				if ( in_array($dbname, $stDbList) )
					$stDb++;
				else
					$selDb = $dbname;
			}
			if (count($dblist) == ($stDb+1)) {
				Session::set('db', 'name', $selDb);
				$db->selectDb(Session::get('db', 'name'));
			}
		}
		// --- end of automatic selection logic

		if (getDbName()) {
			$folder = $db->name();
			echo view(array($folder.'/dblist', 'dblist'), array(), $dblist);
		}
		else {
			print '<span>'.__('Select a database to begin').'.</span>';
		}
		return $dblist;
	}

	function getDBClass() {
		// use the common driver to connect to database
		if ( ! Session::get('auth', 'valid') )
			return array(BASE_PATH . '/lib/db/manager.php', 'DbManager');

		$driver = Session::get('db', 'driver');
		if ( !$driver || empty($driver) )
			return array(BASE_PATH . '/lib/db/manager.php', 'DbManager');

		$lib = BASE_PATH . '/lib/db/'.$driver.'.php';
		$class = 'DB_' . ucfirst( str_replace('/', '_', $driver) );
		return array($lib, $class);
	}

	function execute_request(&$db) {
		// do not append or prepend anything to output if we have to download
		$output = v($_REQUEST["type"]) == 'download' ? false: true;

		if ( $output ) {
			startForm($db);
		}

		if ( isset($_REQUEST["type"]) ) {
			$module_requested = $_REQUEST["type"];
			$_REQUEST["query"] = trim(v($_REQUEST["query"], ""), " \t\r\n;");
			$module = BASE_PATH . "/modules/".$module_requested.".php";
			if (ctype_alpha( $module_requested ) && file_exists($module)) {
				require( BASE_PATH . '/config/modules.php' );
				// check for module access type and allow/disallow as needed
				if (MODULE_ACCESS_MODE == 'deny' && in_array($module_requested, $DENY_MODULES)) {
					createErrorPage();
				}
				else if (MODULE_ACCESS_MODE == 'allow' && !in_array($module_requested, $ALLOW_MODULES)) {
 					createErrorPage();
				} else {
					include($module);
					function_exists('processRequest') ? processRequest($db) : createErrorGrid($db, "");
				}
			}
			else
				createErrorPage();		// unidentified type requested
		}

		if ( $output ) {
			print "</form>\n";
			print "</body></html>";
		}
	}

	function createResultGrid(&$db)
	{
		Session::del('select', 'pkey');
		Session::del('select', 'ukey');
		Session::del('select', 'mkey');
		Session::del('select', 'unique_table');  // this is different from the one used for viewing table data

		$record_limit = Options::get('res-max-count', MAX_RECORD_TO_DISPLAY);
		// <form element is moved to js, see comments there
		print "<div id='results'>";
		print "<table cellspacing=\"0\" width='100%' border=\"0\" class='results postsort' id=\"dataTable\"><thead>\n";

		$f = $db->getFieldInfo();

		// see if all fields come from one table so we can allow editing
		if (Session::get('select', 'can_limit') && count($f) > 0) {
			Session::set('select', 'unique_table', $f[0]->table);
			for($i=1; $i<count($f);$i++) {
				if ($f[$i]->table != Session::get('select', 'unique_table')) {	// bail out, more than one table data
					Session::del('select', 'unique_table');
					break;
				}
			}
		}

		// desc command returns table name as COLUMNS, so we make it empty
		//if (Session::get('select', 'unique_table') == "COLUMNS")
		//	Session::del('select', 'unique_table');

		$ed = Session::get('select', 'can_limit') && (Session::get('select', 'unique_table') == "" ? false : true);// && ($db->numRows() > 0);
		// ------------ print header -----------
		print "<tr id=\"fhead\">";
		print "<th class=\"th tch\"><div>#</div></th>";

		if ($ed)
			print "<th class=\"th_nosort tch\"><div><input class=\"check-all\" type=\"checkbox\"
			onclick=\"resultSelectAll()\" title=\"".__('Select/unselect All records')."\" /></div></th>";

		$v = "";
		// more than one field can be part of a primary key (composite key)
		Session::set('select', 'pkey', array());
		Session::set('select', 'ukey', array());
		Session::set('select', 'mkey', array());

		$fieldNames = '';
		$fieldInfo = json_encode($f);
		$i = 1;
		foreach($f as $fn) {
			$cls = $fn->type == 'numeric' ? "th_numeric" : "th";
			print "<th nowrap=\"nowrap\" class='$cls'><div>";
			if ($fn->pkey == 1) {
				Session::add('select', 'pkey', $fn->name);
				print "<span class='pk' title='".__('Primary key column')."'>&nbsp;</span>";
			}
			else if ($fn->ukey == 1) {
				Session::add('select', 'ukey', $fn->name);
				print "<span class='uk' title='".__('Unique key column')."'>&nbsp;</span>";
			}
			else if ($fn->mkey == 1 && !$fn->blob)		// blob/text fields are FULL TEXT KEYS only
				Session::add('select', 'mkey', $fn->name);

			print $fn->name;

			if (Session::get('select', 'sortcol') == $i) {
				print ( Session::get('select', 'sort') == 'DESC' ? '&nbsp;&#x25BE;' : '&nbsp;&#x25B4' );
			}

			$i++;

			print "</div></th>";
			$fieldNames .= "'" . str_replace("'", "\\'", $fn->name) . "',";
		}

		print "</tr></thead><tbody>\n";

		// ------------ print data -----------
		$j = 0;
		while($r = $db->fetchRow(0, 'num')) {
			$i = 0;
			print "<tr class=\"row\">";
			print "<td class=\"tj\">".($j+1)."</td>";

			if ($ed)
				print "<td class=\"tch\"><input type=\"checkbox\" /></td>";

			foreach($r as $rs) {
				$class = ($rs === NULL) ? "tnl" : ($f[$i]->numeric == 1 ? "tr" : "tl");
				if ($ed) $class .= ' edit';
				//if ($f[$i]->blob)
				//	$class .= $f[$i]->type == 'binary' ? ' blob' : ' text';

				if (!$f[$i]->blob)
					$data = ($rs === NULL) ? "NULL" : (($rs === "") ? "" : htmlspecialchars($rs));
				else
					$data = getBlobDisplay($rs, $f[$i], $j, $ed);

				print "<td nowrap=\"nowrap\" class=\"$class\">$data</td>";
				$i++;
			}
			print "</tr>\n";
			$j++;
			if (Session::get('select', 'can_limit') && $record_limit > 0 && $j >= $record_limit)
				break;
		}

		$numRows = $j;
		print "</tbody></table>";
		print "</div>";

		$editTableName = Session::get('select', 'unique_table');
		$gridTitle = $editTableName == '' ? __('Query Results') :
			str_replace('{{TABLE}}', htmlspecialchars($editTableName), __('Data for {{TABLE}}'));
		print '<div id="title">'. $gridTitle . '</div>';

		$message = '';
		if (Session::get('select', 'can_limit')) { // can limit be applied to this type of query (e.g. show,explain)
			if (Session::get('select', 'limit')) {  // yes, and limit is applied to records by the application
				$total_records = Session::get('select', 'count');
				$total_pages = ceil($total_records / $record_limit);
				$current_page = Session::get('select', 'page');
				$from = (($current_page - 1) * $record_limit) + 1;
				$to = $from + $db->numRows() - 1;
				$message = "<div class='numrec'>".str_replace(array('{{START}}', '{{END}}'), array($from, $to), __('Showing records {{START}} - {{END}}'))."</div>";
			}
			else {
				$total_records = $db->numRows();
				$total_pages = 1;
				$current_page = 1;
				if ($record_limit > 0 && $total_records > $record_limit)
					$message = "<div class='numrec'>".str_replace('{{MAX}}', $record_limit, __('Showing first {{MAX}} records only'))."!</div>";
			}
		}
		else {
			$total_records = $db->numRows();
			$total_pages = 1;
			$current_page = 1;
			$message = "";
		}
		$js = "<script type=\"text/javascript\" language=\"javascript\">\n";
		if (count(Session::get('select', 'pkey')) > 0)
			$js .= "parent.editKey = ".json_encode(Session::get('select', 'pkey')).";\n";
		else if (count(Session::get('select', 'ukey')) > 0)
			$js .= "parent.editKey = ".json_encode(Session::get('select', 'ukey')).";\n";
		else
			$js .= "parent.editKey = [];\n";
		$js .= "parent.editTableName = \"" . htmlspecialchars($editTableName)  ."\";\n";
		$js .= "parent.fieldInfo = ".$fieldInfo.";\n";
		$js .= "parent.queryID = '".md5(Session::get('select', 'query'))."';\n";
		$tm = $db->getQueryTime();
		$js .= "parent.totalRecords = $total_records;\n";
		$js .= "parent.totalPages = $total_pages;\n";
		$js .= "parent.currentPage = $current_page;\n";
		$js .= "parent.transferResultGrid(".$numRows.", '$tm', \"$message\");\n";
		$js .= "parent.addCmdHistory(\"".preg_replace("/[\n\r]+/", "<br/>", htmlspecialchars(Session::get('select', 'query')))."\", 1);\n";
		$js .= "parent.resetFrame();\n";
		$js .= "</script>\n";

		print $js;
	}

	function createSimpleGrid(&$db, $message) {
		print "<div id='results'>";
		print "<div class='message ui-state-default'>$message<span style='float:right'>".__('Quick Search')."&nbsp;<input type=\"text\" id=\"quick-info-search\" maxlength=\"50\" /></div>";

		print "<table cellspacing=\"0\" width='100%' border=\"0\" class='results' id=\"infoTable\"><thead>\n";

		$f = $db->getFieldInfo();

		$ed = false;
		// ------------ print header -----------
		print "<tr id=\"fhead\">";
		print "<th class=\"th\">#</th>";

		$v = "";

		foreach($f as $fn) {
			$cls = $fn->type == 'numeric' ? "th_numeric" : "th";
			print "<th nowrap=\"nowrap\" class='$cls'>";
			print $fn->name."</th>";
		}

		print "</tr></thead><tbody>\n";

		// ------------ print data -----------
		$j = 0;
		while($r = $db->fetchRow(0, 'num')) {
			$i = 0;
			print "<tr id=\"rc$j\" class=\"row\">";
			print "<td class=\"tj\">".($j+1)."</td>";

			foreach($r as $rs) {
				$class = ($rs === NULL) ? "tnl" : ($f[$i]->numeric == 1 ? "tr" : "tl");
				if ($f[$i]->blob)
					$class .= $f[$i]->type == 'binary' ? ' blob' : ' text';

				$data = ($rs === NULL) ? "NULL" : (($rs === "") ? "&nbsp;" : htmlspecialchars($rs));

				print "<td nowrap=\"nowrap\" id=\"r$j"."f$i\" class=\"$class\">$data</td>";
				$i++;
			}
			print "</tr>\n";
			$j++;
		}

		$numRows = $j;
		print "</tbody></table>";
		print "</div>";

		$js = "<script type=\"text/javascript\" language=\"javascript\">\n";
		$tm = $db->getQueryTime();
		$js .= "parent.transferInfoMessage();\n";
		$js .= "parent.resetFrame();\n";
		$js .= "</script>\n";

		print $js;
	}

	function createErrorPage() {
		echo view('error_page');
	}

	// numQueries = number of 'successful' executed queries
	// affectedRows = some rows maybe affected in batch processing, even if error occured
	function createErrorGrid(&$db, $query='', $numQueries=0, $affectedRows=-1) {
		if ($query == '')
			$query = Session::get('select', 'query');

		Session::del('select', 'result');
		Session::del('select', 'pkey');
		Session::del('select', 'ukey');
		Session::del('select', 'mkey');
		Session::del('select', 'unique_table');

		Session::set('select', 'result', array());		// result blob data
		$e = $db->getError();
		print "<div id='results'>\n";
		if ($numQueries > 0) {
			print "<div class=\"message ui-state-default\">";
			$msg = ($numQueries == 1) ? __('1 query successfully executed') : str_replace('{{NUM}}', $numQueries, __('{{NUM}} queries successfully executed'));
			$msg .= ".<br/><br/>".str_replace('{{NUM}}', $affectedRows, __('{{NUM}} record(s) were affected')).".<br/><br/>";
			print $msg . '</div>';
		}
		//else
		//	print "No query was successful";

		$formatted_query = preg_replace("/[\\n|\\r]?[\\n]+/", "<br>", htmlspecialchars($query));
		print "<div class=\"message ui-state-error\">".__('Error occurred while executing the query').":</div><div class=\"message ui-state-highlight\">".htmlspecialchars($e)."</div><div class=\"sql-text ui-state-error\">".$formatted_query."</div>";
		print "</div>";
		print "<script type=\"text/javascript\" language='javascript'> parent.transferResultMessage(-1, '&nbsp;', '".__('Error occurred while executing the query')."');\n";
		print "parent.addCmdHistory(\"".preg_replace("/[\n\r]/", "<br/>", htmlspecialchars(Session::get('select', 'query')))."\");\n";
		print "parent.resetFrame();\n";
		print "</script>\n";
	}

	// batch process will send default params as required
	function createInfoGrid(&$db, $query="", $numQueries=1, $affectedRows=-1, $addHistory=true, $executionTime=false) {
		Session::del('select', 'pkey');
		Session::del('select', 'ukey');
		Session::del('select', 'mkey');
		Session::del('select', 'unique_table');

		if ($affectedRows == -1)
			$affectedRows = $db->getAffectedRows();
		if ($query == "")
			$query = $_REQUEST["query"];
		print "<div id='results'>\n";
		print "<div class=\"message ui-state-default\">";
		$msg = ($numQueries == 1) ? __('1 query successfully executed') : str_replace('{{NUM}}', $numQueries, __('{{NUM}} queries successfully executed'));
		print $msg . ".</div>";
		print "<div class=\"message ui-state-highlight\">".str_replace('{{NUM}}', $affectedRows, __('{{NUM}} record(s) were affected'))."</div>";

		if ($numQueries == 1) {
			$formatted_query = preg_replace("/[\\n|\\r]?[\\n]+/", "<br>", htmlspecialchars($query));
			print "<div class='sql-text ui-state-default'>".$formatted_query."</div>";
		}

		print "</div>";
		$tm = $executionTime ? $executionTime : $db->getQueryTime();
		print "<script type=\"text/javascript\" language='javascript'> parent.transferResultMessage(-1, '$tm', '".str_replace('{{NUM}}', $affectedRows, __('{{NUM}} record(s) updated'))."');\n";
		if ($addHistory)
			print "parent.addCmdHistory(\"".preg_replace("/[\n\r]/", "<br/>", htmlspecialchars($query))."\");\n";
		if (Session::get('db', 'altered')) {
			Session::del('db', 'altered');
			print "parent.objectsRefresh();\n";
		}
		print "parent.resetFrame();\n";
		print "</script>\n";
	}

	function getQueryType($query) {
		$type = array('result'=>FALSE,'can_limit'=>FALSE,'has_limit'=>FALSE,'update'=>FALSE);
		$query = trim($query, " \n\t");
		$query = strtolower(substr($query, 0, 7));  // work on only first few required characters of query
		if(substr($query, 0, 6) == "select" || substr($query, 0, 4) == "desc"
					|| substr($query, 0, 7) == "explain" || substr($query, 0, 4) == "show"
					|| substr($query, 0, 4) == "help" ) {
			$type['result'] = TRUE;
			if (substr($query, 0, 6) == "select") {
				$type['can_limit'] = TRUE; // we don't want to limit results for other queries like 'show...'
				preg_match(LIMIT_REGEXP, $query, $matches);
				if (count($matches) == 3) {
					$type['has_limit'] = true;
				}
			}
		}
		else
			$type['update'] = TRUE;

		return $type;
	}

	function sortQuery($query, $field) {
		$query = trim($query);
		$sort = '';
		$sort_type = Session::get('select', 'sort');
		if (!$sort_type)
			$sort_type = 'ASC';
		$limit = '';
		// find and extract limit clause out of query (must be the last clause, otherwise sorting will not work)
		preg_match(LIMIT_REGEXP, $query, $matches);
		if (count($matches) == 3) {
			$query = trim($matches[1]);
			$limit = trim($matches[2]);
		}
		preg_match(SORT_REGEXP, $query, $matches);
		if (count($matches) > 3) {
			$query = trim($matches[1]);
			// if sorting with same field, change sorting order
			if ( trim($matches[3]) == $field )
				$sort_type = $sort_type == 'ASC' ? 'DESC' : 'ASC';
			else // reset to ascending order
				$sort_type = 'ASC';
		}

		Session::set('select', 'sortcol', $field);
		Session::set('select', 'sort', $sort_type);
		$sort = 'ORDER BY ' . $field . ' ' . $sort_type;

		$query .= ' ' . $sort . ' ' . $limit;

		return $query;
	}

	function getCommandInfo($sql) {
		$info = array('db'=>'', 'dbChanged'=>FALSE, 'dbAltered'=>FALSE, 'setvar'=>FALSE);
		if (preg_match('@^[\s]*USE[[:space:]]*([\S]+)@i', $sql, $match)) {
			$info['db'] = trim($match[1], ' ;');
			$info['dbChanged'] = TRUE;
		} else if (preg_match('/^(CREATE|ALTER|DROP)\s+/i', $sql)) {
			$info['dbAltered'] = true;
		} else if (preg_match('/^SET[\s]+@([a-zA-z0-9_]+|`.*`|\'.*\'|".*")[\s]?=[\s]?(.*)/i', $sql, $matches)) {
			//SET[\s]+@([a-zA-z0-9_]+)[\s]+=[\s]+(.*)
			$info['setvar'] = true;
			$info['variable'] = trim($matches[1]);
			$info['value'] = trim($matches[2]);
		}
		/*preg_match('@^[\s]*(DROP|CREATE)[\s]+(IF EXISTS[[:space:]]+)?(TABLE|DATABASE)[[:space:]]+(.+)@im', $sql)*/
		return $info;
	}

	function startForm($db, $style="margin:0px;overflow:hidden;width:100%;height:100%") {
		print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
		print "<html xmlns=\"http://www.w3.org/1999/xhtml\" style=\"overflow:hidden;width:100%;height:100%\">\n";
		print "<head><title>MyWebSQL</title>\n";
		print "</head><body class=\"dialogbody\" style=\"$style\">\n";
		print '<div id="popup_overlay" class="ui-widget-overlay">';
		print '<div><span><img src="themes/'.THEME_PATH.'/images/loading.gif" alt="" /></span></div>';
		print '</div>';

		print "<script language='javascript' type='text/javascript' src='cache.php?script=".$db->name().",common'></script>\n";
		print "<!--[if lt IE 8]>
					<script type=\"text/javascript\" language=\"javascript\" src=\"cache.php?script=json2\"></script>
				<![endif]-->";
		print "<script language='javascript' type='text/javascript'>
				var EXTERNAL_PATH = '".EXTERNAL_PATH."';
				var THEME_PATH = '".THEME_PATH."';
				var DB = db_" . $db->name() .";
				</script>\n";
		print "<form name='frmquery' id='frmquery' method='post' action='#' enctype='multipart/form-data' onsubmit='return false'>";
		print "<input type='hidden' name='type' value='query' />";
		print "<input type='hidden' name='id' value='' />";
		print "<input type='hidden' name='name' value='' />";
		print "<input type='hidden' name='query' value='' />";
	}

	function sanitizeCreateCommand($type, $cmd) {
		$str = preg_replace("/[\\n|\\r]?[\\n]+/", "<br>", htmlspecialchars($cmd));
		return $str;

		/*if ($type == "table")
			return $str;
		else if ($type == "view")
		{
			$str = str_replace(" DEFINER=", "<br>DEFINER=", $str);
			$str = str_replace(" SQL SECURITY ", "<br>SQL SECURITY ", $str);
			$str = str_replace(" AS (", "<br> AS<br>(", $str);
		}
		else if ($type == "procedure")
		{
			$str = str_replace(" DEFINER=", "<br>DEFINER=", $str);
			$str = str_replace(" PROCEDURE ", "<br>PROCEDURE ", $str);
			$str = str_replace("BEGIN", "<br>BEGIN<br>", $str);
			$str = str_replace(" END", "<br>END", $str);

		}
		else if ($type == "function")
		{
			$str = str_replace(" DEFINER=", "<br>DEFINER=", $str);
			$str = str_replace(" FUNCTION ", "<br>FUNCTION ", $str);
			$str = str_replace("BEGIN", "<br>BEGIN<br> ", $str);
			$str = str_replace(" END", "<br>END", $str);
		}
		else if ($type == "trigger")
		{
			$str = str_replace("\n", "<br>", $str);
		}

		return $str;*/
	}

	function getBlobDisplay($rs, $info, $numRecord, $editable) {
		//$pattern = '/[\x00-\x08\x0E-\x1F\x7F]/'; for binary matching
		$binary = ($info->type == 'binary') ? true : false;
		$length = strlen($rs);
		$size = format_bytes($length);
		$span = '<span class="i">';

		if ($rs === NULL)
			$span .= "NULL";
		else if ($rs === "")
			$span .= "&nbsp;";
		else {
			if (MAX_TEXT_LENGTH_DISPLAY >= $length)
				$span .= htmlspecialchars($rs);
			else if ($binary)
				$span .= str_replace( '{{SIZE}}', $size, __('Blob Data [{{SIZE}}]') );
			else
				$span .= str_replace( '{{SIZE}}', $size, __('Text Data [{{SIZE}}]') );
		}

		$extra = "";
		$btype = "text";

		if ($binary) {
			include(BASE_PATH . "/config/blobs.php");
			foreach($blobTypes as $k => $v) {
				if ( $v[1] && matchFileHeader($rs, $v[1]) ) {
					$btype = $k;
					break;
				}
			}
			$extra = 'onclick="vwBlb(this, '.$numRecord.', \''.$btype.'\')"';
		}

		$span .= "</span>";

		// for binary fields, editing is separated from interface
		if ($binary) {
			$span .= "<span title=\"" . str_replace('{{NUM}}', $length, __('Click to view/edit column data [{{NUM}} Bytes]')). "\" class=\"blob $btype\" $extra>&nbsp;</span>";
			return $span;
		}

		$span .= '<span class="d" style="display:none">' . htmlspecialchars($rs) . '</span>';

		// for non editable text fields, we need to show the data associated
		if (!$editable && $rs !== NULL && MAX_TEXT_LENGTH_DISPLAY < $length) {
			$extra = 'onclick="vwTxt(this, &quot;'.$size.'&quot;, \''.$btype.'\')"';
			$span .= "<span title=\"" . str_replace('{{NUM}}', $length, __('Click to view/edit column data [{{NUM}} Bytes]')). "\" class=\"blob $btype\" $extra>&nbsp;</span>";
		}

		return $span;
	}

	function setDbVar( $variable, $value ) {
		Session::set('vars', $variable, $value);
	}

	function loadDbVars(&$db) {
		$vars = Session::get_all('vars');
		foreach($vars as $variable => $value) {
			$query = 'SET @'.$variable.' = '.$value;
			$db->query($query);
		}
	}
?>