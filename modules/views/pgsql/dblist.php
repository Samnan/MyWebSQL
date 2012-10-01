<?php
	Html::select("dblist", "id='dblist' onchange='dbSelect()'", "", "width:100%");
	foreach($data as $dbname)
		Html::option($dbname, $dbname, Session::get('db', 'name') == $dbname ? "selected=\"selected\"" : "");
	Html::endselect();
?>