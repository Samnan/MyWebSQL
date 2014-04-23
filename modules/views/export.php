<link href='cache.php?css=theme,default,alerts' rel="stylesheet" />

<style>
	div#db_objects { margin-top:5px;padding:3px;overflow:auto;height:99%;width:95%;border:3px double #efefef }
	div.objhead 	{ background-color:#ececec; padding: 5px; margin: 0 0 3px 0 }
	span.toggler 	{ display:inline-block; float:right; cursor: pointer; font-size:16px; margin: -5px 0 0 0 }
	div.obj 			{ padding:5px; margin:0 0 0 20px }
</style>

<div id="popup_wrapper">
	<div id="popup_contents">
		<table border="0" cellpadding="5" cellspacing="8" style="width: 100%;height:98%">
		<tr>
		<td align="left" valign="top" width="45%" height="99%">
			<?php echo __('Select objects to include in export'); ?><br />
			<div id="db_objects">
				<?php echo __('Either the database is empty, or there was an error retrieving list of database objects'); ?>.<br/>
				<?php echo __('Please try closing and re-opening this dialog again'); ?>.
			<div>
		</td>

		<td align="left" valign="top" width="55%">
		<fieldset>
			<legend><?php echo __('Export type'); ?></legend>
			<table border="0" cellspacing="10" cellpadding="5" width="100%">
				<tr><td valign="top">
				<input type='radio' name='exptype' id='exptype1' value="struct" /><label class="right" for='exptype1'><?php echo __('Structure'); ?></label>
				</td></tr>
				<tr><td valign="top">
				<input type='radio' name='exptype' id='exptype2' value="data" /><label class="right" for='exptype2'><?php echo __('Table Data'); ?></label>
				</td></tr>
				<tr><td valign="top">
				<input type='radio' name='exptype' checked="1" id='exptype3' value="all" /><label class="right" for='exptype3'><?php echo __('Structure and Table Data'); ?></label>
				</td></tr>
			</table>
		</fieldset>

		<fieldset>
			<legend><?php echo __('Options'); ?></legend>
			<table border="0" cellspacing="10" cellpadding="5" width="100%">
				<tr><td valign="top">
				<input type='checkbox' name='auto_null' id='auto_null' /><label class="right" for='auto_null'><?php echo __('Set Auto increment field values to NULL'); ?></label>
				</td></tr>
				
				<tr><td valign="top">
				<input type='checkbox' name='exclude_type' id='exclude_type' /><label class="right" for='exclude_type'><?php echo __('Exclude Table type'); ?></label>
				</td></tr>
				
				<tr><td valign="top">
				<input type='checkbox' name='exclude_charset' id='exclude_charset' /><label class="right" for='exclude_charset'><?php echo __('Exclude Table Character set'); ?></label>
				</td></tr>

				<tr><td valign="top">
				<input type='checkbox' name='dropcmd' id='dropcmd' /><label class="right" for='dropcmd'><?php echo __('Add DROP command before create statements'); ?></label>
				</td></tr>

				<tr><td valign="top">
				<input type='checkbox' name='emptycmd' id='emptycmd' /><label class="right" for='emptycmd'><?php echo __('Add TRUNCATE command before insert statements'); ?></label>
				</td></tr>

				<tr><td valign="top">
				<input type='checkbox' name='bulkinsert' id='bulkinsert' /><label class="right" for='bulkinsert'><?php echo __('Generate Bulk insert statements'); ?></label>
				</td></tr>

				<tr><td valign="top">
				<input disabled="disabled" type='checkbox' name='bulklimit' id='bulklimit' /><label class="right" for='bulklimit'><?php echo __('Maximum size of SQL statement'); ?></label>
				&nbsp;<input disabled="disabled" type="text" name="bulksize" id="bulksize" style="height:12px;vertical-align:bottom;width:30px" />&nbsp;KB
				</td></tr>
			</table>
		</fieldset>

		</td>
		</tr>
		</table>
	</div>
	<div id="popup_footer">
		<div id="popup_buttons">
			<input type='button' id="btn_export" value='<?php echo __('Export'); ?>' />
		</div>
	</div>
</div>

<script type="text/javascript" language='javascript' src="cache.php?script=common,jquery,ui,query,options,alerts"></script>
<script type="text/javascript" language="javascript">
window.title = "<?php echo __('Export Database'); ?>";
var exportType = 'export';
<?php
	foreach( $data as $name => $list ) {
		echo "var {$name} = " . json_encode( $list ) .";\n";
	}
?>

$(function() {
	// we do not want to show the popup overlay when form is submitted
	$('#popup_overlay').remove();
	$('#btn_export').button().click(function() { exportData() });
	$("#bulkinsert").click(function() {
		if( $(this).prop("checked") )
			$("#bulklimit").removeAttr("disabled");
		else
		$("#bulklimit").attr("disabled","disabled");
	});
	$("#bulklimit").click(function() {
		if( $(this).prop("checked") )
			$("#bulksize").removeAttr("disabled");
		else
			$("#bulksize").attr("disabled","disabled");
	});
	$("[name=exptype]").click(function() {
		if( $(this).val() == "struct" ) {
			$("#auto_null").removeAttr("disabled");
			$("#exclude_type").removeAttr("disabled");
			$("#exclude_charset").removeAttr("disabled");
			$("#bulkinsert").removeAttr("checked").attr("disabled","disabled");
			$("#bulklimit").removeAttr("checked").attr("disabled","disabled");
			$("#bulksize").val('').attr("disabled","disabled");
			$("#emptycmd").removeAttr("checked").attr("disabled","disabled");
			$("#dropcmd").removeAttr("disabled");
		} else if( $(this).val() == "data" ) {
			$("#exclude_type").removeAttr("checked").attr("disabled","disabled");
			$("#exclude_charset").removeAttr("checked").attr("disabled","disabled");
			$("#auto_null").removeAttr("checked").attr("disabled","disabled");
			$("#bulkinsert").removeAttr("disabled");
			$("#emptycmd").removeAttr("disabled");
			$("#dropcmd").removeAttr("checked").attr("disabled","disabled");
		} else {
			$("#auto_null").removeAttr("disabled");
			$("#exclude_type").removeAttr("disabled");
			$("#exclude_charset").removeAttr("disabled");
			$("#bulkinsert").removeAttr("disabled");
			$("#emptycmd").removeAttr("disabled");
			$("#dropcmd").removeAttr("disabled");
		}
	});
<?php
	if ( count($data) > 0 ) {
?>
		$('#db_objects').html('');
<?php
		foreach( $data as $name => $list ) {
			echo "uiShowObjectList({$name}, '{$name}', '" . __( ucfirst($name) ) . "');\n";
		}
	}
?>
	$('.selectall').click(function(e) {
		chk = $(this).attr('checked');
		chk ? $(this).parent().next().find('input').attr('checked', "checked") : $(this).parent().next().find('input').removeAttr('checked');
	});

	$('#db_objects .toggler').click(function() {
		$(this).parent().next().toggle();
		if ($(this).hasClass('c')) {
			$(this).removeClass('c').html('&#x25B4;');
		} else {
			$(this).addClass('c').html('&#x25BE;');
		}
		return false;
	});

});
</script>
<?php
	echo getGeneratedJS();
?>