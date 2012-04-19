<link href="cache.php?css=theme,default,help" rel="stylesheet" />

<div id="popup_wrapper">

	<div class="docinfo">
		<?php echo str_replace('{{LINK}}', '', __('To see most up-to-date help contents, please visit {{LINK}}')); ?>
		<a target="_blank" href="{{PROJECT_SITEURL}}/docs">MyWebSQL Online Documentation</a>
	</div>
	
	<ul class="links">
	{{LINKS}}
	</ul>
	<div class="content">
	{{CONTENT}}
	</div>

</div>

<script language="javascript" src="cache.php?script=jquery,help" type="text/javascript"></script>
<script type="text/javascript" language="javascript">
	window.title = "<?php echo __('Help'); ?>";
	$(function() {
		$('ul.links a').click(function() {
			page = $(this).attr('href').replace('#', '');
			showHelpPage(page);
		});
	});
</script>