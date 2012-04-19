<link href='cache.php?css=theme,default,alerts,results' rel="stylesheet" />

<style>
	div#db_objects { margin-top:5px;padding:3px;overflow:auto;height:300px;width:220px;border:3px double #efefef }
	div.objhead 	{ background-color:#ececec; padding: 5px; margin: 0 0 3px 0 }
	span.toggler 	{ display:inline-block; float:right; cursor: pointer; font-size:16px; margin: -5px 0 0 0 }
	div.obj 			{ padding:5px; margin:0 0 0 20px }
</style>

<div id="popup_wrapper">
	<div id="popup_contents">
		<table border="0" cellpadding="5" cellspacing="4" style="width: 100%;height:100%">
		<tr>
		<td align="left" valign="top" width="45%">
			<?php echo __('Select tables to be analyzed/repaired'); ?><br />
			<div id="db_objects">
				<?php echo __('Either the database is empty, or there was an error retrieving list of database objects'); ?>.<br/>
				<?php echo __('Please try closing and re-opening this dialog again'); ?>.
			<div>
		</td>

		<td align="left" valign="top" width="55%">
		<fieldset>
			<legend><?php echo __('Operation to perform'); ?></legend>
			<table border="0" cellspacing="10" cellpadding="5" width="100%">
				<tr><td valign="top">
				<input type='radio' name='optype' id='optype1' value="analyze" checked="1" /><label class="right" for='optype1'><?php echo __('Analyze'); ?></label>
				</td></tr>
				<tr><td valign="top">
				<input type='radio' name='optype' id='optype2' value="check" /><label class="right" for='optype2'><?php echo __('Check'); ?></label>
				</td></tr>
				<tr><td valign="top">
				<input type='radio' name='optype' id='optype3' value="optimize" /><label class="right" for='optype3'><?php echo __('Optimize'); ?></label>
				</td></tr>
				<tr><td valign="top">
				<input type='radio' name='optype' id='optype4' value="repair" /><label class="right" for='optype4'><?php echo __('Repair'); ?></label>
				</td></tr>
			</table>
		</fieldset>

		<fieldset>
			<legend><?php echo __('Options'); ?></legend>
			<table border="0" cellspacing="10" cellpadding="5" width="100%">
				<tr><td valign="top">
				<input type='checkbox' name='skiplog' id='skiplog' /><label class="right" for='skiplog'><?php echo __('Skip Binary logging'); ?></label>
				</td></tr>

				<tr id="check_options" style="display:none"><td valign="top">
					<table border="0" cellspacing="5" cellpadding="3" width="100%">
						<tr><td><input type='radio' name='checktype' id='checktype1' value="default" checked="1" /><label class="right" for='checktype1'><?php echo __('Default'); ?></label></td></tr>
						<tr><td><input type='radio' name='checktype' id='checktype2' value="quick" /><label class="right" for='checktype2'><?php echo __('Quick'); ?></label></td></tr>
						<tr><td><input type='radio' name='checktype' id='checktype3' value="fast" /><label class="right" for='checktype3'><?php echo __('Fast'); ?></label></td></tr>
						<tr><td><input type='radio' name='checktype' id='checktype4' value="medium" /><label class="right" for='checktype4'><?php echo __('Medium'); ?></label></td></tr>
						<tr><td><input type='radio' name='checktype' id='checktype5' value="extended" /><label class="right" for='checktype5'><?php echo __('Extended'); ?></label></td></tr>
						<tr><td><input type='radio' name='checktype' id='checktype6' value="changed" /><label class="right" for='checktype6'><?php echo __('Changed'); ?></label></td></tr>
					</table>
				</td></tr>
				
				<tr id="repair_options" style="display:none"><td valign="top">
					<table border="0" cellspacing="5" cellpadding="3" width="100%">
						<tr><td><input type='checkbox' name='repairtype' id='repairtype1' value="quick" /><label class="right" for='repairtype1'><?php echo __('Quick'); ?></label></td></tr>
						<tr><td><input type='checkbox' name='repairtype' id='repairtype2' value="extended" /><label class="right" for='repairtype2'><?php echo __('Extended'); ?></label></td></tr>
						<tr><td><input type='checkbox' name='repairtype' id='repairtype3' value="usefrm" /><label class="right" for='repairtype3'><?php echo __('Use Frm files (MyISAM tables)'); ?></label></td></tr>
					</table>
				</td></tr>
				
			</table>
		</fieldset>
		</td>
		</tr>
		</table>
	</div>
	<div id="popup_footer">
		<div id="popup_buttons">
			<input type='button' id="btn_repair" value='<?php echo __('Submit'); ?>' />
		</div>
	</div>
</div>

<script type="text/javascript" language='javascript' src="cache.php?script=common,jquery,ui,query,options,alerts"></script>
<script type="text/javascript" language="javascript">
window.title = "<?php echo __('Repair Tables'); ?>";
var repairType = 'analyze';
var tables = {{TABLELIST}};

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
	$('#btn_repair').button().click(function() { repairTables() });
	if (tables.length == 0)
		return;

	$('#db_objects').html('');
	show_list(tables, 'tables', 'db_tables', 'Tables');

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
	
	$('#optype1').click(function() { $('#check_options').hide(); $('#repair_options').hide(); });
	$('#optype2').click(function() { $('#check_options').show(); $('#repair_options').hide(); });
	$('#optype3').click(function() { $('#check_options').hide(); $('#repair_options').hide(); });
	$('#optype4').click(function() { $('#check_options').hide(); $('#repair_options').show(); });

});
</script>
