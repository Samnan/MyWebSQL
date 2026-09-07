<link href='cache.php?css=theme,default,alerts' rel="stylesheet" />

<style>
	div#db_objects { padding:3px;overflow:auto;height:310px;width:95%;border:3px double #efefef }
	div.objhead 	{ background-color:#ececec; padding: 5px; margin: 0 0 3px 0 }
	span.toggler 	{ display:inline-block; float:right; cursor: pointer; font-size:16px; margin: -5px 0 0 0 }
	div.obj 			{ padding:5px; margin:0 0 0 20px }

	div#backup_progress { display:none; margin:3px 3px 6px 3px; padding:6px 10px }
	div#backup_bar 		{ height:14px }
	div#backup_stage 	{ margin:6px 0 0 0; font-weight:bold; white-space:nowrap; overflow:hidden; text-overflow:ellipsis }
	div#backup_detail 	{ margin:2px 0 0 0; color:#666 }
</style>

<div id="popup_wrapper">
	<div id="popup_contents">
		<input type="hidden" name="token" id="backup_token" value="" />
		<div id="backup_message">{{MESSAGE}}</div>
		<div id="backup_progress" class="ui-widget-content ui-corner-all">
			<div id="backup_bar"></div>
			<div id="backup_stage">&nbsp;</div>
			<div id="backup_detail">&nbsp;</div>
		</div>
		<table border="0" cellpadding="5" cellspacing="8" style="width: 100%;height:90%">
		<tr>
		<td align="left" valign="top" width="45%">
			<div id="db_objects">
				<?php echo __('Either the database is empty, or there was an error retrieving list of database objects'); ?>.<br/>
				<?php echo __('Please try closing and re-opening this dialog again'); ?>.
			</div>
		</td>

		<td align="left" valign="top" width="55%">
		<fieldset>
			<legend><?php echo __('Backup type'); ?></legend>
			<table border="0" cellspacing="10" cellpadding="5" width="100%">
				<tr><td valign="top">
				<input type='radio' name='exptype' id='exptype1' value="struct" /><label class="right" for='exptype1'><?php echo __('Structure'); ?></label>
				</td></tr>
				<tr><td valign="top">
				<input type='radio' name='exptype' id='exptype2' value="data" /><label class="right" for='exptype2'><?php echo __('Table Data'); ?></label>
				</td></tr>
				<tr><td valign="top">
				<input type='radio' name='exptype' checked="1" id='exptype3' value="all" /><label class="right" for='exptype3'><?php echo __('Structure and Table Data'); ?></label>
				</td></tr>
			</table>
		</fieldset>

		<fieldset>
			<legend><?php echo __('Options'); ?></legend>
			<table border="0" cellspacing="10" cellpadding="5" width="100%">
				<tr><td valign="top">
				<input type='checkbox' name='auto_null' id='auto_null' /><label class="right" for='auto_null'><?php echo __('Set Auto increment field values to NULL'); ?></label>
				</td></tr>

				<tr><td valign="top">
				<input type='checkbox' name='dropcmd' id='dropcmd' /><label class="right" for='dropcmd'><?php echo __('Add DROP command before create statements'); ?></label>
				</td></tr>

				<tr><td valign="top">
				<input type='checkbox' name='emptycmd' id='emptycmd' /><label class="right" for='emptycmd'><?php echo __('Add TRUNCATE command before insert statements'); ?></label>
				</td></tr>
				
				<tr><td valign="top">
				<label><?php echo __('Backup filename'); ?>:</label><input type='text' name='filename' id='filename' value="{{FILENAME}}" style="width:120px" />
				</td></tr>
				
			</table>
		</fieldset>

		<fieldset>
			<legend><?php echo __('Compression'); ?></legend>
			<table border="0" cellspacing="10" cellpadding="5" width="100%">
				<tr><td valign="top">
				<input type='radio' value="" name='compression' id='compress_none' checked="checked" /><label class="right" for='compress_none'><?php echo __('No Compression'); ?></label>
				</td>
<?php if (function_exists('bzopen')) { ?>
				<td valign="top">
				<input type='radio' value="bz" name='compression' id='compress_bzip' /><label class="right" for='compress_bzip'><?php echo __('BZip'); ?></label>
				</td>
<?php }
if (function_exists('gzopen')) { ?>
				<td valign="top">
				<input type='radio' value="gz" name='compression' id='compress_gzip' /><label class="right" for='compress_gzip'><?php echo __('GZip'); ?></label>
				</td>
<?php } ?>
				</tr>
			</table>
		</fieldset>
		
		</td>
		</tr>
		</table>
	</div>
	<div id="popup_footer">
		<div id="popup_buttons">
			<input type='button' id="btn_export" value='<?php echo __('Export'); ?>' />
		</div>
	</div>
</div>

<script type="text/javascript" language='javascript' src="cache.php?script=common,jquery,ui,query,options,alerts"></script>
<script type="text/javascript" language="javascript">
window.title = "<?php echo __('Backup Database'); ?>";
var exportType = 'backup';
<?php
	foreach( $data as $name => $list ) {
		echo "var {$name} = " . json_encode( $list ) .";\n";
	}
?>

/*
 * The backup is written on the server and can run for a long time. Instead of submitting
 * the form into this iframe and staring at an unchanged page until php is done, the request
 * is sent in the background and the progress is polled from status.php.
 */
var backupToken = '';
var backupTimer = null;
var backupRunning = false;
var backupPollFails = 0;

function backupToken_new() {
	var chars = 'abcdefghijklmnopqrstuvwxyz0123456789', s = '';
	for (var i = 0; i < 20; i++)
		s += chars.charAt(Math.floor(Math.random() * chars.length));
	return s;
}

function backupEscape(text) {
	return $('<div/>').text(text === null || text === undefined ? '' : text).html();
}

function backupSize(bytes) {
	bytes = bytes ? bytes : 0;
	var units = ['B', 'KB', 'MB', 'GB', 'TB'], i = 0;
	while (bytes >= 1024 && i < units.length - 1) { bytes = bytes / 1024; i++; }
	return (i === 0 ? bytes : bytes.toFixed(1)) + ' ' + units[i];
}

function backupTime(seconds) {
	seconds = Math.max(0, Math.round(seconds ? seconds : 0));
	var m = Math.floor(seconds / 60);
	return (m > 0 ? m + 'm ' : '') + (seconds % 60) + 's';
}

function backupMessage(text, cls) {
	$('#backup_message').html('<div class="message ' + cls + '">' + backupEscape(text) + '</div>');
}

function backupStop() {
	backupRunning = false;
	if (backupTimer) { window.clearTimeout(backupTimer); backupTimer = null; }
	$('#btn_export').button('enable');
}

function backupStart() {
	if (backupRunning)
		return;

	if ($('#db_objects input[type=checkbox]:checked').not('.selectall').length == 0) {
		jAlert(__('Select objects to include in backup'), __('Backup Database'));
		return;
	}

	backupToken = backupToken_new();
	backupRunning = true;
	backupPollFails = 0;
	$('#backup_token').val(backupToken);

	var frm = document.frmquery;
	frm.type.value = 'dl';
	frm.id.value = 'backup';
	frm.name.value = '';
	frm.query.value = '';

	$('#btn_export').button('disable');
	$('#backup_progress').show();
	$('#backup_bar').progressbar({ value: 0 });
	$('#backup_stage').html(__('Starting backup') + '...');
	$('#backup_detail').html('&nbsp;');
	backupMessage(__('Backup in progress'), 'ui-state-highlight');

	$.ajax({ type: 'POST',
		url: '?',
		data: 'q=wrkfrm&' + $('#frmquery').serialize(),
		dataType: 'json',
		success: function(res) { if (res) backupDone(res); },
		// a proxy may drop this connection while php keeps working, so the poll,
		// not this request, decides when the backup is over
		error: function() { backupPoll(); }
	});

	backupTimer = window.setTimeout(backupPoll, 700);
}

function backupPoll() {
	if (!backupRunning)
		return;

	$.ajax({ type: 'GET',
		url: 'status.php?type=backup&id=' + backupToken + '&_=' + (new Date()).getTime(),
		dataType: 'json',
		success: function(res) {
			if (!backupRunning)
				return;
			backupPollFails = 0;
			if (res && res.s == 1) {
				if (res.state == 'done' || res.state == 'error') {
					backupDone(res);
					return;
				}
				backupProgress(res);
			}
			backupTimer = window.setTimeout(backupPoll, 1000);
		},
		error: function() {
			if (!backupRunning)
				return;
			if (++backupPollFails > 10) {
				backupStop();
				$('#backup_stage').html(__('Backup failed'));
				backupMessage(__('Lost contact with the server while the backup was running'), 'ui-state-error');
				return;
			}
			backupTimer = window.setTimeout(backupPoll, 2000);
		}
	});
}

function backupProgress(res) {
	if (res.state == 'starting') {
		$('#backup_stage').html(__('Starting backup') + '...');
		return;
	}

	$('#backup_bar').progressbar('value', res.c ? res.c : 0);

	var label = __('Exporting') + ' ' + res.done + '/' + res.total;
	if (res.object)
		label += ' – ' + backupEscape(res.type + ' ' + res.object);
	$('#backup_stage').html(label);

	$('#backup_detail').html( backupSize(res.bytes) + ' · ' + (res.totalrows ? res.totalrows : 0) + ' ' + __('rows')
		+ ' · ' + backupTime(res.elapsed) );
}

function backupDone(res) {
	if (!backupRunning)		// the poll and the request itself both report the result
		return;
	backupStop();

	var elapsed = (res.elapsed !== undefined) ? res.elapsed : (res.updated - res.started);

	if (res.state == 'error') {
		$('#backup_stage').html(__('Backup failed'));
		$('#backup_detail').html('&nbsp;');
		backupMessage(res.message ? res.message : __('Failed to create database backup'), 'ui-state-error');
		return;
	}

	$('#backup_bar').progressbar('value', 100);
	$('#backup_stage').html(__('Backup complete'));
	$('#backup_detail').html( backupEscape(res.file) + ' · ' + backupSize(res.size ? res.size : res.bytes)
		+ ' · ' + (res.totalrows ? res.totalrows : 0) + ' ' + __('rows') + ' · ' + backupTime(elapsed) );
	backupMessage(res.message ? res.message : __('Database backup successfully created'), 'ui-state-highlight');
}

$(function() {
	$('#popup_overlay').remove();  // progress is reported in the dialog itself, no blocking overlay
	$('#btn_export').button().click(function() { backupStart() });

<?php
	if ( count($data) > 0 ) {
?>
		$('#db_objects').html('');
<?php
		foreach( $data as $name => $list ) {
			echo "uiShowObjectList({$name}, '{$name}', '" . __( ucfirst($name) ) . "');\n";
		}
	}
?>
	$('.selectall').click(function(e) {
		chk = $(this).attr('checked');
		chk ? $(this).parent().next().find('input').attr('checked', "checked") : $(this).parent().next().find('input').removeAttr('checked');
	});

	$('#db_objects .toggler').click(function() {
		$(this).parent().next().toggle();
		if ($(this).hasClass('c')) {
			$(this).removeClass('c').html('&#x25B4;');
		} else {
			$(this).addClass('c').html('&#x25BE;');
		}
		return false;
	});

});
</script>
<?php
	echo getGeneratedJS();
?>