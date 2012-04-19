<link href='cache.php?css=theme,default,grid,alerts,mysqlcolors' rel="stylesheet" />

<div id="popup_wrapper">
	<div id="popup_contents">
		{{MESSAGE}}
		<div id="blob-holder">
			<pre class="blob" id="blob-data">{{BLOBDATA}}</pre>
		</div>
	</div>
	
	<div id="popup_footer">
		<div id="popup_buttons">
			{{BLOB_TOOLBAR}}
			<?php echo str_replace('{{TYPE}}', '', __('Show blob data as: {{TYPE}}')); ?>
			<select name='blobtype' onchange="blobChangeType()">{{BLOBOPTIONS}}</select>
	</div>
</div>

<input type="hidden" name="act" value="" />
<input type="hidden" name="blob_value" value="" />

<script type="text/javascript" language='javascript' src="cache.php?script=common,jquery,ui,query,cookies,settings,blobs"></script>
<script type="text/javascript" language="javascript">
window.title = "<?php echo __('Blob data for column {{NAME}}'); ?>";
$(function() {
	document.frmquery.type.value = 'viewblob';
	document.frmquery.id.value = '{{ID}}';
	document.frmquery.name.value = '{{NAME}}';
	document.frmquery.query.value = '{{QCODE}}';
	var table = "{{TABLE}}";
	
	$('#btnSaveBlob').button().click(blobSave);
});
</script>


