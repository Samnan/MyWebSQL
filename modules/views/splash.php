<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8;" />
<title>MyWebSQL</title>
<link rel="SHORTCUT ICON" href="favicon.ico" />
<style type="text/css">
* {
	font-family: Tahoma;
	font-size: 8pt;
	font-weight: normal;
	margin: 0;
	padding: 0;
	text-align: left;
}
label {
	display: inline-block;
	margin: 0 5px 0 5px;
	vertical-align: baseline;
}
input {
	padding: 3px;
}
input[type="text"], input[type="password"] {
	width: 190px;
}
input[type="button"], input[type="submit"] {
	min-width: 100px;
	text-align: center;
}
select {
	padding: 3px;
	width: 202px;
}
select option{
	padding: 2px;
}
div#splash {
	width: 474px;
	height: 384px;
	background: url(img/splash.png) no-repeat;
	text-align: center;
	overflow: hidden;
	margin: auto;
	position: relative;
}

span.version {
	position: relative;
	display: inline;
	right: -95px;
	top: 80px;
	font: normal 8pt Tahoma, sans-serif;
	color: #8C8A8A;
}

span.website {
	position: relative;
	display: inline;
	right: -60px;
	bottom: -310px;
	font: bold 2em/1.25em Helvetica, sans-serif;
}
span.website a {
	font: bold 8pt Tahoma, sans-serif;
	color: #3a980c;
	text-decoration: none;
}

span.website a:hover {
	color: #c00017;
	border-bottom: 1px dotted #c00017;
}

div.auth {
	width: 320px;
	margin: 0 auto;
	text-align: center;
	background: transparent;
	border: 4px double #ECECEC;
	padding: 4px;
}
div.auth div {
	text-align: center;
	padding: 4px;
}
div.auth label {
	width: 100px;
	display: inline-block;
}

div.login {
	margin: 100px auto;
	font-family: verdana;
	font-size: 9pt;
	text-align: center;
}
div.msg {
	position: relative;
	top: 95px;
	width: 325px;
	padding: 2px;
	margin: 0 auto;
	color: #cc0000;
	font-weight: bold;
	text-align: center;
}

</style>
</head>
<body style="background-color:white">
<div style="border:none;position:absolute;left:0px;top:0px;width:100%;height:100%;background-color:white;display:block;">
	<table border="0" width="100%" style="height:100%">
		<tr><td height="100%" valign="middle" align="center" style="text-align:center">
			<div id="splash">
				<span class="version"><?php echo __('version'); ?> {{APP_VERSION}}</span>
				<span class="website"><a target="_blank" href="{{PROJECT_SITEURL}}" title="<?php echo __('Visit Project website'); ?>"><?php echo __('Visit Project website'); ?></a></span>
				{{MESSAGE}}
				{{FORM}}
				</div>
			</td>
		</tr>
	</table>
</div>
<script language="javascript" type="text/javascript" src="cache.php?script={{SCRIPTS}}"></script>
{{EXTRA_SCRIPT}}
</body></html>
