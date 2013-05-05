<link href="cache.php?css=theme,default,help" rel="stylesheet" />
<script language="javascript" src="cache.php?script=jquery,ui,cookies,options,settings" type="text/javascript"></script>

<div id="popup_wrapper">

	<ul class="links">
	<?php
	foreach($data['pages'] as $x=>$y) {
			if ($data['page'] == $x)
				echo "<li class=\"current\"><img border=\"0\" align=\"absmiddle\" src='img/options/o_$x".".gif' alt=\"\" />$y</li>";
			else
				echo "<li><a href=\"#$x\"><img border=\"0\" align=\"absmiddle\" src='img/options/o_$x".".gif' alt=\"\" />$y</a></li>";
		}
	?>
	</ul>
	<div class="content">
	{{CONTENT}}
	</div>

</div>

<script type="text/javascript" language="javascript">
	window.title = "<?php echo __('Options'); ?>";
	var COOKIE_LIFETIME = <?php echo COOKIE_LIFETIME; ?>;
	$(function() {
		$('ul.links a').click(function() {
			page = $(this).attr('href').replace('#', '');
			navigatePage('options', page);
		});

		$("#save").button().click(function() { $(this).button("disable"); });
		$("#reset").button().click( optionsReset );

		// enable save button when any setting is changed
		$("input[type=radio], input[type=checkbox]").click(function() {
			$("#save").button("enable");
		});
		$("input[type=text]").change(function() {
			$("#save").button("enable");
		});
	});
</script>