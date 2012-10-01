<link href='cache.php?css=theme,default,alerts,grid' rel="stylesheet" />

<div id="popup_wrapper">
	<div id="popup_contents">
		{{MESSAGE}}
		<?php if ( $data['progress'] ) { ?>
			<input type="hidden" name="<?php echo ini_get("session.upload_progress.name"); ?>" value="import">
		<?php } ?>
		<div class="padded"><?php echo __('Select SQL batch file to import'); ?></div>
		<div class="padded"><input type='file' name='impfile' size="40" /><div class="progressbar" id="import-progress" style="display:inline-block;height:20px;width:200px">&nbsp;</div></div>
		<div class="padded"><input type='checkbox' name='ignore_errors' id="ignore_errors" value='yes' /><label class="right" for="ignore_errors"><?php echo __('Continue processing even if error occurs'); ?></label></div>
	</div>
	<div id="popup_footer">
		<div id="popup_buttons">
			<input type='button' id="btn_import" value='<?php echo __('Import'); ?>' />
		</div>
	</div>
</div>

<script type="text/javascript" language='javascript' src="cache.php?script=common,jquery,ui,query,options,alerts"></script>
<script type="text/javascript" language='javascript'>
window.title = "<?php echo __('Import'); ?>";
<?php if ( $data['progress'] ) { ?>
$('#popup_overlay').remove();
<?php } ?>
$(function() {
	$('#btn_import').button().click(function() {
		if (document.frmquery.impfile.value == '') {
			jAlert('<?php echo __('Select SQL batch file to import'); ?>');
			return false;
		}
		$('#btn_import').button("option", { disabled:true, text:__("Please wait...") });
		<?php if ( $data['progress'] ) { // update progressbar and dialog title to show upload progress... ?>
			//$( "#import-progress" ).progressbar({ value: 30 });
			//uiShowStatus("#import-progress", "import", "import", 1000);
		<?php } ?>
		wrkfrmSubmit('import', '', '', '');
	});
})
if ({{REFRESH}})
	window.parent.objectsRefresh();
</script>