<div class="ui-helper-hidden">

	<div id="dialog-template" title="Dialog">
		<div class="dialog_msg ui-widget ui-state-highlight ui-corner-all" style="margin:5px; padding:5px"><?php echo __('Please wait ...'); ?></div>
		<iframe src="javascript:false" class="dialog_contents" frameborder="0"></iframe>
	</div>

	<div id="dialog-dbcreate" title="<?php echo __('Create Database'); ?>">
		<form name="dbform" action="" method="get" onsubmit="return dbCreate(1);">
			<div id="popup_content">
				<div>
					<label for="dbname"><?php echo __('Database name'); ?>:</label>
					<input type="text" name="dbname" id="dbname" class="text ui-widget-content" />
				</div>
				<div>
					<input type="checkbox" name="dbselect" id="dbselect" class="text ui-widget-content" />
					<label class="right" for="dbselect"><?php echo __('Select database after creation'); ?></label>
				</div>
			</div>
		</form>
	</div>

	<div id="dialog-text-editor" title="<?php echo __('Edit'); ?>">
		<textarea name="text-editor" id="text-editor" class="text ui-widget-content"></textarea>
	</div>

</div>