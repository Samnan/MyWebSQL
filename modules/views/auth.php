<div class="auth">
	<div>
		<label><?php echo __('User ID'); ?>:</label><input type="text" name="auth_user" size="30" value="{{LOGINID}}"/>
	</div>
	<div>
		<label><?php echo __('Password'); ?>:</label><input type="password" name="auth_pwd" size="30" />
	</div>
	<?php
		$serverList = getServerList();
		if ($serverList !== false && (count($serverList) > 1 || ALLOW_CUSTOM_SERVER_TYPES)) {
	?>
	<div>
		<label><?php echo __('Server'); ?>:</label><select name="server" id="server">
		<?php
			$selServer = v($_REQUEST['server']);
			foreach($serverList as $server => $host) {
				if ($selServer == $server)
					echo '<option value="'.htmlspecialchars($server).'" selected="selected">'.htmlspecialchars($server ? $server : __('Custom Server')).'</option>';
				else
					echo '<option value="'.htmlspecialchars($server).'">'.htmlspecialchars($server).'</option>';
			}
			if(ALLOW_CUSTOM_SERVERS) {
				echo '<option value="">'.htmlspecialchars(__('Custom Server')).'</option>';
			}
		?>
		</select>
	</div>
	<?php
		}
	?>
	<?php if(ALLOW_CUSTOM_SERVERS): $stypes = explode(',', ALLOW_CUSTOM_SERVER_TYPES); ?>
	<div id="custom-server" style="display:none">
		<label><?php echo __('Server Address'); ?>:</label>
		<input type="text" name="server_name" id="server-name" size="30" value="{{SERVER_NAME}}" />
		<select name="server_type" id="server-type">
			<?php if(in_array('mysql', $stypes)): ?><option value='mysql'>MySQL</option><?php endif; ?>
			<?php if(in_array('pgsql', $stypes)): ?><option value='pgsql'>PostgreSQL</option><?php endif; ?>
			<?php if(in_array('sqlite', $stypes)): ?><option value='sqlite'>SQLite</option><?php endif; ?>
		</select>
	</div>
	<?php endif; ?>
	<div>
		<label><?php echo __('Language'); ?>:</label><select name="lang">
		<?php $langList = getLanguageList();
			$selLang = v($_REQUEST['lang']);
			if ($selLang == '')
				$selLang = v($_COOKIE['lang']);
			if ($selLang == '' && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$selLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
			}
			foreach($langList as $lang => $name) {
				if ($selLang == $lang)	// REQUEST[lang] is filled by auth module if login is encrypted
					echo '<option value="'.$lang.'" selected="selected">'.$name.'</option>';
				else
					echo '<option value="'.$lang.'">'.$name.'</option>';
			}
		?>
		</select>
	</div>
	<div>
		<input type="submit" value="<?php echo __('Login'); ?>" />
	</div>
</div>
<script language="javascript" type="text/javascript">
document.dbform.auth_user.focus();
<?php if(ALLOW_CUSTOM_SERVERS): ?>
document.onready = function() {
	$("#server").change(function() {
		if ($(this).val() == "") {
			$("#custom-server").show();
			$("#server-name").focus();
			$("span.website").hide();
		} else {
			$("#custom-server").hide();
			$("span.website").show();
		}
	});
<?php if(ALLOW_CUSTOM_SERVERS && $selServer == ''): ?>
	$("#server").trigger("change");
<?php endif; ?>
};
<?php endif; ?>
</script>
