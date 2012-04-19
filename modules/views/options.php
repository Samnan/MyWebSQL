<link href="cache.php?css=default,help" rel="stylesheet" />
<script language="javascript" src="cache.php?script=jquery,options" type="text/javascript"></script>
<div>
	<table width='496px' class='maintb' border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="120px" nowrap="nowrap" valign="top">
			<table width="100%" border="0" cellspacing="0" cellpadding="2">
			{{LINKS}}
			</table>
		</td>
		<td valign="top">
			<div class='content'>
				{{CONTENT}}
			</div>
		</td>
	</tr>
	</table>
</div>

<script type="text/javascript" language='javascript'>
window.title = "<?php echo __('Options'); ?>";
$(function() {
	optionsLoad('{{PAGE}}');
});
</script>