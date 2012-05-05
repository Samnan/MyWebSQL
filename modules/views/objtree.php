<?php

	print '<ul id="tablelist" class="filetree">';
	$tables = $data['tables'];
	print '<li id="tables"><span class="tablef">'.__('Tables').'</span><span class="count">'.count($tables).'</span>';
	if (count($tables) > 0) {
		foreach($tables as $key=>$table) {
			$id = 't_'.Html::id($table);
			$table = htmlspecialchars($table);
			print '<ul><li><span class="file otable" id="'.$id.'"><a href=\'javascript:objDefault("table", "'.$id.'")\'>'.$table.'</a></span></li></ul>';
		}
	}
	print "</li>\n";

	if (isset($data['views'])) {
		$tables = $data['views'];
		print '<li id="views"><span class="viewf">'.__('Views').'</span><span class="count">'.count($tables).'</span>';
		if (count($tables) > 0) {
			foreach($tables as $key=>$table) {
				$id = 'v_'.Html::id($table);
				$table = htmlspecialchars($table);
				print '<ul><li><span class="file oview" id="'.$id.'"><a href=\'javascript:objDefault("view", "'.$id.'")\'>'.$table.'</a></span></li></ul>';
			}
		}
		print "</li>\n";
	}
	
	if (isset($data['procedures'])) {
		$tables = $data['procedures'];
		print '<li id="procs"><span class="procf">'.__('Procedures').'</span><span class="count">'.count($tables).'</span>';
		if (count($tables) > 0)	{
			foreach($tables as $key=>$table)	{
				$id = 'p_'.Html::id($table);
				$table = htmlspecialchars($table);
				print '<ul><li><span class="file oproc" id="'.$id.'"><a href=\'javascript:objDefault("procedure", "'.$id.'")\'>'.$table.'</a></span></li></ul>';
			}
		}
		print "</li>\n";
	}
	
	if (isset($data['functions'])) {
		$tables = $data['functions'];
		print '<li id="funcs"><span class="funcf">'.__('Functions').'</span><span class="count">'.count($tables).'</span>';
		if (count($tables) > 0)	{
			foreach($tables as $key=>$table)	{
				$id = 'f_'.Html::id($table);
				$table = htmlspecialchars($table);
				print '<ul><li><span class="file ofunc" id="'.$id.'"><a href=\'javascript:objDefault("function", "'.$id.'")\'>'.$table.'</a></span></li></ul>';
			}
		}
		print "</li>\n";
	}
	
	if (isset($data['triggers'])) {
		$tables = $data['triggers'];
		print '<li id="trigs"><span class="trigf">'.__('Triggers').'</span><span class="count">'.count($tables).'</span>';
		if (count($tables) > 0)	{
			foreach($tables as $key=>$table)	{
				$id = 't_'.Html::id($table);
				$table = htmlspecialchars($table);
				print '<ul><li><span class="file otrig" id="'.$id.'"><a href=\'javascript:objDefault("trigger", "'.$id.'")\'>'.$table.'</a></span></li></ul>';
			}
		}
		print "</li>\n";
	}
	
	if (isset($data['events'])) {
		$tables = $data['events'];
		print '<li id="events"><span class="evtf">'.__('Events').'</span><span class="count">'.count($tables).'</span>';
		if (count($tables) > 0)	{
			foreach($tables as $key=>$table)	{
				$id = 'e_'. Html::id($table);
				$table = htmlspecialchars($table);
				print '<ul><li><span class="file oevt" id="'.$id.'"><a href=\'javascript:objDefault("event", "'.$id.'")\'>'.$table.'</a></span></li></ul>';
			}
		}
		print "</li>\n";
	}
	print '</ul>';

?>