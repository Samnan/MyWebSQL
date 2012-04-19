<div class="auth">
	<div>
		<label><?php echo __('User ID'); ?>:</label><input type="text" name="auth_user" size="30" value="{{LOGINID}}"/>
	</div>
	<div>
		<label><?php echo __('Password'); ?>:</label><input type="password" name="auth_pwd" size="30" />
	</div>
	<?php
		$serverList = getServerList();
		if ($serverList !== false && count($serverList) > 1) {
	?>
	<div>
		<label><?php echo __('MySQL Server'); ?>:</label><select name="server">
		<?php
			$selServer = v($_REQUEST['server']);
			foreach($serverList as $server => $host) {
				if ($selServer == $server)
					echo '<option value="'.htmlspecialchars($server).'" selected="selected">'.htmlspecialchars($server).'</option>';
				else
					echo '<option value="'.htmlspecialchars($server).'">'.htmlspecialchars($server).'</option>';
			}
		?>
		</select>
	</div>
	<?php
		}
	?>
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
</script>