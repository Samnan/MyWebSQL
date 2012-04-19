<strong>
    <table border="0" cellpadding="5" cellspacing="0" style="width: 100%">
		<tr>
            <td colspan="2" class='title'>
                <strong>Record editing</strong></td>
        </tr>
        <tr>
            <td valign="top">
				<input type='checkbox' name='qedit1' checked="1" id='qedit1' /><label for='qedit1'>Generate update queries, instead of directly updating records (<b>Recommended</b>)</label>
				</td>
		</tr>
		<tr>
            <td valign="top">
				<input type='checkbox' name='qedit2' checked="1" id='qedit2' /><label for='qedit2'>Generate delete queries, instead of directly deleting records (<b>Recommended</b>)</label>
            </td>
		</tr>
		<tr>
            <td valign="top" align="right">
				<input type='button' name='saver' value='  Apply  ' onclick="optionsSave('qedit1', 'qedit2')" />
            </td>
		</tr>
    </table>
	<br />
</strong>