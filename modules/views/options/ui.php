<fieldset style="margin: 10px">
	<legend><?php echo __('Interface'); ?></legend>
	<table border="0" cellspacing="10" cellpadding="5" width="100%">
		<tr><td valign="top">
		<input type='checkbox' name='ui-tables-count' id='ui-tables-count' <?php if(Options::get('ui-tables-count', false)) echo 'checked="checked" '; ?>/><label class="right" for='ui-tables-count'><?php echo __('Show record count with table names'); ?></label>
		</td></tr>
		<tr><td valign="top">
			<label><?php echo __('Sort Table listing by'); ?></label>
			<table border="0" cellspacing="2" cellpadding="1" width="100%">
				<tr><td valign="top">
					<input type='radio' name='ui-tables-sort' id="ui-tables-sort-name" value="" <?php if(Options::get('ui-tables-sort', '') == '') echo ' checked="checked"'; ?>/><label class="right" for="ui-tables-sort-name"><?php echo __('Name'); ?></label>
				</td></tr>
				<tr><td valign="top">
					<input type='radio' name='ui-tables-sort' id="ui-tables-sort-time" value="time" <?php if(Options::get('ui-tables-sort', '') == 'time') echo ' checked="checked"'; ?>/><label class="right" for="ui-tables-sort-time"><?php echo __('Last Update Time'); ?></label>
				</td></tr>
			</table>
		</td></tr>
		<tr><td valign="top">
		<input type='submit' id="save" value="<?php echo __('Save'); ?>" />
		</td></tr>
	</table>
</fieldset>
<script type="text/javascript" language="javascript">
   $(function () {
   	$("#save").click(function () {
   		optionsSet('ui-tables-count', $('#ui-tables-count').prop("checked") ? 1 : 0);
   		optionsSet('ui-tables-sort', $('input:radio[name="ui-tables-sort"]:checked').val());
   	});
   });
</script>