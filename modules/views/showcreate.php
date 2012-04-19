<div id='results'>
	<div class="message"><?php echo __('Create command for {{TYPE}} {{NAME}}'); ?></div>
	<div class="sql_text">
		{{COMMAND}}
	</div>
</div>

<script type="text/javascript" language="javascript">
//parent.editKey = '';
//parent.editTableName = '';
//parent.fieldNames = new Array();
parent.transferInfoMessage();
parent.addCmdHistory("{{SQL}}");
</script>
