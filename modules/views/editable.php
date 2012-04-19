<link href='cache.php?css=theme,default,grid,alerts,mysqlcolors' rel="stylesheet" />

<div id="popup_wrapper">
	<div id="popup_contents">
		<div id="grid-messages">{{MESSAGE}}</div>

		<div id="grid-tabs">
			<ul>
				<li><a href="#tab-fields"><?php echo __('Basic Information'); ?></a></li>
				<li><a href="#tab-props"><?php echo __('Table Properties'); ?></a></li>
				<li><a href="#tab-messages"><?php echo __('Messages'); ?></a></li>
			</ul>
			<div class="ui-corner-bottom">
				<div id="tab-fields">
					<div class="input">
						<span><?php echo __('Table Name'); ?>:</span><span><input type="text" size="20" name="table-name" id="table-name" value="{{TABLE_NAME}}" /><span>
					</div>
					<table border="0" cellspacing="1" cellpadding="2" id="table_grid"><tbody>
						<tr id='fhead'>
							<th style="width:120px"><?php echo __('Field Name'); ?></th>
							<th style="width:75px"><?php echo __('Data Type'); ?></th>
							<th style="width:65px"><?php echo __('Length'); ?></th>
							<th style="width:90px"><?php echo __('Default value'); ?></th>
							<th style="width:65px"><?php echo __('Unsigned'); ?></th>
							<th style="width:65px"><?php echo __('Zero Fill'); ?></th>
							<th style="width:85px"><?php echo __('Primary Key'); ?></th>
							<th style="width:100px"><?php echo __('Auto Increment'); ?></th>
							<th style="width:70px"><?php echo __('Not NULL'); ?></th>
						</tr>
					</tbody></table>
				</div>
				<div id="tab-props">
					<div class="input"><span><?php echo __('Table Engine (type)'); ?>:</span><span><select name="enginetype" id="enginetype">{{ENGINE}}</select><span></div>
					<div class="input float"><span><?php echo __('Character Set'); ?>:</span><span><select name="charset" id="charset">{{CHARSET}}</select><span></div>
					<div class="input"><span><?php echo __('Collation'); ?>:</span><span><select name="collation" id="collation">{{COLLATION}}</select><span></div>
					<div class="input"><span><?php echo __('Comment'); ?>:</span><span><input type="text" size="40" name="comment" id="comment" value="{{COMMENT}}" style="width:488px" /><span></div>
				</div>
				<div id="tab-messages">
					<?php echo __('Waiting for table information to be submitted'); ?>
				</div>
			</div>
		</div>
	</div>

	<div id="popup_footer">
		<div id="popup_buttons">
			<input type='button' id='btn_add' value='<?php echo __('Add field'); ?>' />
			<input type='button' id='btn_del' value='<?php echo __('Delete selected field'); ?>' />
			<input type='button' id='btn_clear' value='<?php echo __('Clear Table Information'); ?>' />
			<input type='button' id='btn_submit' value='<?php echo __('Submit'); ?>' tabindex="1" />
		</div>
	</div>

</div>

<div id="dialog-list" title="<?php echo __('List of values'); ?>">
	<div class="padded">
		<div>
			<select size="8" name="list-items" id="list-items"></select>
		</div>
		<div>
			<input type="text" name="item" id="item" class="text ui-widget-content" />
		</div>
	</div>
</div>

<script type="text/javascript" language='javascript' src="cache.php?script=common,jquery,ui,editable,position,query,cookies,settings,alerts,hotkeys"></script>
<script type="text/javascript" language="javascript">
window.title = ({{ALTER_TABLE}} ? "<?php echo __('Edit table - '); ?>{{TABLE_NAME}}" : "<?php echo __('Create Table'); ?>");
var rowInfo = {{ROWINFO}};

$(function() {
	setupEditable({{ALTER_TABLE}});
});
</script>