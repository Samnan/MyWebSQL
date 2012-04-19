<?php
/**
 * This file is a part of MyWebSQL package
 *
 * @file:      modules/processes.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2011 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

	function processRequest(&$db) {
		// html and form is started by calling function
		print "<link href='cache.php?css=theme,default,alerts,results' rel=\"stylesheet\" />\n";
		
		$type = 'note';
		if (isset($_REQUEST['prcid']) && is_array($_REQUEST['prcid'])) {
			$killed = $missed = array();
			foreach($_REQUEST['prcid'] as $process_id) {
				if (ctype_digit($process_id) && killProcess($db, $process_id)) {
					$killed[] = $process_id;
				} else {
					$missed[] = $process_id;
				}
			}
			if (count($killed) > 0) {
				$msg = str_replace('{{PID}}', implode(',', $killed), __('The process with id [{{PID}}] was killed'));
				$type = 'success';
			} else {
				$msg = str_replace('{{PID}}', implode(',', $missed), __('No such process [id = {{PID}}]'));
				$type = 'warning';
			}
		}
		else
			$msg = __('Select a process and click the button to kill the process');
		
		displayProcessList($db, $msg, $type);
	}
	
	function displayProcessList(&$db, $msg, $type="note") {
		print "<input type='hidden' name='q' value='wrkfrm' />";
		print "<input type='hidden' name='type' value='processes' />";
		print "<input type='hidden' name='id' value='' />";
		
		print "<table border=0 cellspacing=2 cellpadding=2 width='100%'>";
		if ($msg != "") {
			$div = '<div class="'.$type.'">'.$msg.'</div>';
			print "<tr><td height=\"25\">$div</td></tr>";
		}
		print "<tr><td colspan=2 valign=top>";
		
		if ($db->query("show full processlist")) {
			print "<table class='results postsort' border=0 cellspacing=1 cellpadding=2 width='100%' id='processes'><tbody>";
			print "<tr id='fhead'><th></th><th class='th'>".__('Process ID')."</th><th class='th'>".__('Command')."</th><th class='th'>".__('Time')."</th><th class='th'>".__('Info')."</th></tr>";
			
			while($row = $db->fetchRow())
				print "<tr class='row'><td class=\"tch\"><input type=\"checkbox\" name='prcid[]' value='".$row['Id']."' /></td><td class='tl'>$row[Id]</td><td class='tl'>$row[Command]</td><td class='tl'>$row[Time]</td><td class='tl'>$row[Info]</td></tr>";
			
			print "</tbody></table>";
			
			print "<tr><td colspan=2 align=right><div id=\"popup_buttons\"><input type='submit' id=\"btn_kill\" name='btn_kill' value='".__('Kill Process')."' /></div></td></tr>";

			print "<script type=\"text/javascript\" language='javascript' src=\"cache.php?script=common,jquery,ui,query,sorttable,tables\"></script>\n";
			
			print "<script type=\"text/javascript\" language='javascript'>
				window.title = \"".__('Process Manager')."\";
				$('#btn_kill').button().click(function() { document.frmquery.submit(); });
				setupTable('processes', {sortable:true, highlight:true, selectable:true});
			</script>";
		}
		else
			print __('Failed to get process list');
	}
	
	function killProcess(&$db, $id) {
		if ($id) {
			traceMessage("killing process with id $id");
			if ($db->query("kill '".$db->escape($id)."'"))
				return true;
		}
		return false;
	}

?>