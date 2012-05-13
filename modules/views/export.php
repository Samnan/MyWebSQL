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
				<input type='checkbox' name='dropcmd' id='dropcmd' /><label class="right" for='dropcmd'><?php echo __('Add DROP command before create statements'); ?></label>
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
		html += '<div class="obj"><input checked="checked" type="checkbox" name="' + name + '[]" id="' + name + '_' + id + '" value="'
				+ value + '" /><label class="right" for="' + name + '_' + id + '">' + table + '</label></div>';
	}
	if (html != '')
	{
		html = '<div class="objhead ui-widget-header"><input checked="checked" type="checkbox" class="selectall" id="h_' + title
				+ '" /><label class="right" for="h_' + title + '">' + title + '</label><span class="toggler">&#x25B4;</span></div><div>'
				+ html + '</div>';
		$('#db_objects').append(html);
	}
}

$(function() {
	$('#popup_overlay').remove();  // we do not want to show the popup overlay when form is submitted
	$('#btn_export').button().click(function() { exportData() });
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
	}); //.next().hide();

});
</script>
<?php
	echo getGeneratedJS();
?>