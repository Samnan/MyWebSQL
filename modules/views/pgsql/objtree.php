<?php

	print '<ul id="tablelist" class="filetree">';
	foreach($data['schemas'] as $schema) {

		$schema_id = 's_'.Html::id($schema);
		print '<li id="'.$schema_id.'"><span class="schmf">'.htmlspecialchars($schema).'</span>';

		print '<ul class="filetree">';
		$tables = isset($data['tables'][$schema]) ? $data['tables'][$schema] : array();
		print '<li><span class="tablef" data-parent="'.htmlspecialchars($schema).'">'.__('Tables').'</span>';
		if(count($tables) > 0)
			print '<span class="count">'.count($tables).'</span>';
		foreach($tables as $key=>$table) {
			$id = 't_' . $schema_id . '_' .Html::id($table);
			$table = htmlspecialchars($table);
			print '<ul><li><span class="file otable" id="'.$id.'"><a data-parent="'.htmlspecialchars($schema).'" href=\'javascript:objDefault("table", "'.$id.'", "'.$schema_id.'")\'>'.$table.'</a></span></li></ul>';
		}
		print "</li>\n";

		if (isset($data['views'])) {
			$tables = isset($data['views'][$schema]) ? $data['views'][$schema] : array();
			print '<li><span class="viewf" data-parent="'.htmlspecialchars($schema).'">'.__('Views').'</span>';
			if(count($tables) > 0)
				print '<span class="count">'.count($tables).'</span>';
			foreach($tables as $key=>$table) {
				$id = 'v_'. $schema_id . '_' .Html::id($table);
				$table = htmlspecialchars($table);
				print '<ul><li><span class="file oview" id="'.$id.'"><a data-parent="'.htmlspecialchars($schema).'" href=\'javascript:objDefault("view", "'.$id.'", "'.$schema_id.'")\'>'.$table.'</a></span></li></ul>';
			}
			print "</li>\n";
		}

		if (isset($data['procedures'])) {
			$tables = isset($data['procedures'][$schema]) ? $data['procedures'][$schema] : array();
			print '<li><span class="procf" data-parent="'.htmlspecialchars($schema).'">'.__('Procedures').'</span>';
			if(count($tables) > 0)
				print '<span class="count">'.count($tables).'</span>';
			foreach($tables as $key=>$table)	{
				$id = 'p_'. $schema_id . '_' .Html::id($table);
				$table = htmlspecialchars($table);
				print '<ul><li><span class="file oproc" id="'.$id.'"><a data-parent="'.htmlspecialchars($schema).'" href=\'javascript:objDefault("procedure", "'.$id.'", "'.$schema_id.'")\'>'.$table.'</a></span></li></ul>';
			}
			print "</li>\n";
		}

		if (isset($data['functions'])) {
			$tables = isset($data['functions'][$schema]) ? $data['functions'][$schema] : array();
			print '<li><span class="funcf" data-parent="'.htmlspecialchars($schema).'">'.__('Functions').'</span>';
			if(count($tables) > 0)
				print '<span class="count">'.count($tables).'</span>';
			foreach($tables as $key=>$table)	{
				$id = 'f_'. $schema_id . '_' .Html::id($table);
				$table = htmlspecialchars($table);
				print '<ul><li><span class="file ofunc" id="'.$id.'"><a data-parent="'.htmlspecialchars($schema).'" href=\'javascript:objDefault("function", "'.$id.'", "'.$schema_id.'")\'>'.$table.'</a></span></li></ul>';
			}
			print "</li>\n";
		}

		if (isset($data['triggers'])) {
			$tables = isset($data['triggers'][$schema]) ? $data['triggers'][$schema] : array();
			print '<li><span class="trigf" data-parent="'.htmlspecialchars($schema).'">'.__('Triggers').'</span>';
			if(count($tables) > 0)
				print '<span class="count">'.count($tables).'</span>';
			foreach($tables as $key=>$table)	{
				$id = 't_'. $schema_id . '_' .Html::id($table);
				$table = htmlspecialchars($table);
				print '<ul><li><span class="file otrig" id="'.$id.'"><a data-parent="'.htmlspecialchars($schema).'" href=\'javascript:objDefault("trigger", "'.$id.'", "'.$schema_id.'")\'>'.$table.'</a></span></li></ul>';
			}
			print "</li>\n";
		}

		if (isset($data['events'])) {
			$tables = isset($data['events'][$schema]) ? $data['events'][$schema] : array();
			print '<li><span class="evtf" data-parent="'.htmlspecialchars($schema).'">'.__('Events').'</span>';
			if(count($tables) > 0)
				print '<span class="count">'.count($tables).'</span>';
			foreach($tables as $key=>$table)	{
				$id = 'e_'. $schema_id . '_' .Html::id($table);
				$table = htmlspecialchars($table);
				print '<ul><li><span class="file oevt" id="'.$id.'"><a data-parent="'.htmlspecialchars($schema).'" href=\'javascript:objDefault("event", "'.$id.'", "'.$schema_id.'")\'>'.$table.'</a></span></li></ul>';
			}
			print "</li>\n";
		}
		print '</ul>';

		print '</li>';
	}
	print '</ul>';
?>