<div id='results'>
	<div class='heading1'><?php echo __('Server information'); ?></div>
	<div class="hinfo">
		<div class="label"><?php echo __('Mysql version'); ?></div><div class="info">{{SERVER_VERSION}}</div>
		<div class="label"><?php echo __('Version comment'); ?></div><div class="info">{{SERVER_COMMENT}}</div>
	</div>
	<div class='heading1'><?php echo __('Character sets'); ?></div>
	<div class="hinfo">
		<div class="label"><?php echo __('Server character set'); ?></div><div class="info">{{SERVER_CHARSET}}</div>
		<div class="label"><?php echo __('Client character set'); ?></div><div class="info">{{CLIENT_CHARSET}}</div>
		<div class="label"><?php echo __('Database character set'); ?></div><div class="info">{{DATABASE_CHARSET}}</div>
		<div class="label"><?php echo __('Results character set'); ?></div><div class="info">{{RESULT_CHARSET}}</div>
	</div>
</div>

<script type="text/javascript" language="javascript">
parent.transferInfoMessage();
{{JS}}
</script>
