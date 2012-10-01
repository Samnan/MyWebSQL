<?php
	echo '<ul id="tablelist" class="dblist">';
	foreach($data as $dbname)
		echo '<li><span class="odb"><a href="javascript:dbSelect(\''.$dbname.'\')">'.htmlspecialchars($dbname).'</a></span>';
	echo '</ul>';
?>