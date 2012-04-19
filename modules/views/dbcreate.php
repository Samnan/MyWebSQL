<script type="text/javascript" language='javascript' src="cache.php?script=jquery"></script>
<div id="results">
	<div class="success"><?php echo __('New database successfully created'); ?> [ {{DB_NAME}} ]</div>
</div>

<script type="text/javascript" language='javascript'>
	parent.addCmdHistory("{{SQL}}");
	parent.transferResultMessage(-1, '{{TIME}}', '<?php echo __('New database successfully created'); ?>');
	if ({{REDIRECT}})
		parent.window.location = parent.window.location;
	else {
		parent.$('#dblist').append('<option name="{{DB_NAME}}">{{DB_NAME}}</option>');
		var $r = parent.$("#dblist option");
		$r.sort(function(a, b) {
			if (a.text < b.text) return -1;
			if (a.text == b.text) return 0;
			return 1;
		});
		$($r).remove();
      parent.$("#dblist").append( $( $r ) );
	}
</script
