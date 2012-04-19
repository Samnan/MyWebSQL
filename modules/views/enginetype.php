<link href='cache.php?css=theme,default,alerts,grid' rel="stylesheet" />
<div id="popup_wrapper">
	<div id="popup_contents">
		{{MESSAGE}}
		<div id="grid-tabs" class="padded">
			<div class="input"><span><?php echo __('Table Engine (type)'); ?>:</span><span><select name="enginetype" id="enginetype">{{ENGINE}}</select><span></div>
		</div>
	</div>
	<div id="popup_footer">
		<div id="popup_buttons">
			<input type='button' id="btn_alter" value='<?php echo __('Submit'); ?>' />
		</div>
	</div>
</div>

<script type="text/javascript" language='javascript' src="cache.php?script=common,jquery,ui,query,options"></script>
<script type="text/javascript" language='javascript'>
window.title = "<?php echo __('Change Table Type'); ?>";
$('#btn_alter').button().click(function() {
	wrkfrmSubmit('enginetype', 'alter', '{{TABLE_NAME}}', '')
});
</script>