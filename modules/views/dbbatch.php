<link href='cache.php?css=theme,default,alerts' rel="stylesheet" />

<style>
	div#db_objects { margin-top:5px;padding:3px;overflow:auto;height:300px;width:220px;border:3px double #efefef }
	div.objhead 	{ background-color:#ececec; padding: 5px; margin: 0 0 3px 0 }
	span.toggler 	{ display:inline-block; float:right; cursor: pointer; font-size:16px; margin: -5px 0 0 0 }
	div.obj 			{ padding:5px; margin:0 0 0 20px }
</style>

<div id="popup_wrapper">
	<div id="popup_contents">
		<table border="0" cellpadding="5" cellspacing="8" style="width: 100%;height:100%">
		<tr>
		<td align="left" valign="top" width="45%">
			<?php echo __('Select objects to operate upon'); ?><br />
			<div id="db_objects">
				<?php echo __('Either the database is empty, or there was an error retrieving list of database objects'); ?>.<br/>
				<?php echo __('Please try closing and re-opening this dialog again'); ?>.
			<div>
		</td>

		<td align="left" valign="top" width="55%">
		<fieldset>
			<legend><?php echo __('Operations to perform'); ?></legend>
			<table border="0" cellspacing="10" cellpadding="5" width="100%">
				<tr><td valign="top">
					<label for='old_prefix'><?php echo __('Delete prefix string from name'); ?></label><input type='text' name='old_prefix' id='old_prefix' maxlength="10" style="width:70px" />
				</td></tr>
				
				<tr><td valign="top">
					<label for='new_prefix'><?php echo __('Add prefix string to name'); ?></label><input type='text' name='new_prefix' id='new_prefix' maxlength="10" style="width:70px" />
				</td></tr>
				
				<tr><td valign="top">
				<input type='checkbox' name='dropcmd' id='dropcmd' /><label class="right" for='dropcmd'><?php echo __('DROP selected database objects'); ?></label>
				</td></tr>
			</table>
		</fieldset>
		
		<fieldset>
			<legend><?php echo __('Generate SQL'); ?></legend>
			<table border="0" cellspacing="10" cellpadding="5" width="100%">
				<tr><td valign="top">
					<label for='command'><?php echo __('Command text'); ?></label><input type='text' name='command' id='command' maxlength="100" style="width:180px" />
				</td></tr>
			</table>
		</fieldset>
		
		</td>
		</tr>
		</table>
	</div>
	<div id="popup_footer">
		<div id="popup_buttons">
			<input type='button' id="btn_submit" value='<?php echo __('Submit'); ?>' />
		</div>
	</div>
</div>

<script type="text/javascript" language='javascript' src="cache.php?script=common,jquery,ui,query,options,alerts"></script>
<script type="text/javascript" language="javascript">
window.title = "<?php echo __('Batch operations'); ?>";
var tables = {{TABLELIST}};
var views = {{VIEWLIST}};
var procs = {{PROCLIST}};
var funcs = {{FUNCLIST}};
var triggers = {{TRIGGERLIST}};
var events = {{EVENTLIST}};

function show_list(list, name, divid, title)
{
	html = '';
	for(i=0; i<list.length; i++)
	{
		table = list[i];
		id = str_replace(/[\s\"']/, '', table);
		value = str_replace(/[\"]/, '&quot', table);
		html += '<div class="obj"><input type="checkbox" name="' + name + '[]" id="' + name + '_' + id + '" value="'
				+ value + '" /><label class="right" for="' + name + '_' + id + '">' + table + '</label></div>';
	}
	if (html != '')
	{
		html = '<div class="objhead ui-widget-header"><input type="checkbox" class="selectall" id="h_' + title
				+ '" /><label class="right" for="h_' + title + '">' + title + '</label><span class="toggler">&#x25B4;</span></div><div>'
				+ html + '</div>';
		$('#db_objects').append(html);
	}
}

$(function() {
	$('#btn_submit').button().click(function() {
		if ( $("#db_objects").find("input[type=checkbox]").filter(":checked").length == 0 ) {
		 	jAlert(__('Select objects to operate upon'));
		} else if ($("#dropcmd").prop("checked")) {
			// if drop command is selected, confirm user for the operation
			jConfirm(__('Are you sure you want to DROP selected objects?'), __('Confirm Action'), function(result) {
				if (result)
					wrkfrmSubmit('dbbatch', 'batch', '', '');
			}, '');
		 } else if ( $("#old_prefix").val() == '' && $("#new_prefix").val() == '' && $("#command").val() == '' ) {
			jAlert(__('Please select one or more operations to perform'));
		 } else {
			wrkfrmSubmit('dbbatch', 'batch', '', '');
		 }
	});
	
	$("#dropcmd").click(function() {
		var on = $(this).prop("checked");
		$("#new_prefix").add("#old_prefix").attr("disabled", on);
	});
	
	if (tables.length == 0 && views.length == 0 && procs.length == 0 && funcs.length == 0 && triggers.length == 0)
		return;

	$('#db_objects').html('');
	show_list(tables, 'tables', 'db_tables', __('Tables'));
	show_list(views, 'views', 'db_views', __('Views'));
	show_list(procs, 'procs', 'db_procs', __('Procedures'));
	show_list(funcs, 'funcs', 'db_funcs', __('Functions'));
	show_list(triggers, 'triggers', '#db_triggers', __('Triggers'));
	show_list(events, 'events', '#db_events', __('Events'));

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
