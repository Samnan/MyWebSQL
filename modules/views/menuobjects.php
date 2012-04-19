<div style="display:none">

<div id="db-menu">
	<ul class="dropdown context">
		<li><a href="javascript:dbSelect([name])" title="<?php echo __('Use this database'); ?>"><?php echo __('Use Database'); ?></a></li>
	</ul>
</div>

<div id="object-menu">
	<ul class="dropdown context">
		<li><a class="itable" href="javascript:tableCreate()" title="<?php echo __('Create a new table in the database'); ?>"><?php echo __('Create Table'); ?></a></li>
		<li class="option mysql5 mysqli sqlite"><a class="iview" href="javascript:objCreate(1)" title="<?php echo __('Create a new view in the database'); ?>"><?php echo __('Create View'); ?></a></li>
		<li class="option mysql5 mysqli"><a class="iproc" href="javascript:objCreate(2)" title="<?php echo __('Create a new stored procedure in the database'); ?>"><?php echo __('Create Stored Procedure'); ?></a></li>
		<li class="option mysql5 mysqli"><a class="ifunc" href="javascript:objCreate(3)" title="<?php echo __('Create a new user defined function in the database'); ?>"><?php echo __('Create Function'); ?></a></li>
		<li class="option mysql5 mysqli sqlite"><a class="itrig" href="javascript:objCreate(4)" title="<?php echo __('Create a new trigger in the database'); ?>"><?php echo __('Create Trigger'); ?></a></li>
		<li class="option mysql5 mysqli"><a class="ievt" href="javascript:objCreate(5)" title="<?php echo __('Create a new event in the database'); ?>"><?php echo __('Create Event'); ?></a></li>
	</ul>
</div>

<div id="table-menu">
<ul class="dropdown context">
	<li><a href="tableSelect([name])"><?php echo __('Select statement'); ?></a></li>
	<li><a href="tableInsert([name])"><?php echo __('Insert statement'); ?></a></li>
	<li><a href="tableUpdate([name])"><?php echo __('Update statement'); ?></a></li>
	<li class="option mysql4 mysql5 mysqli"><a href="tableDescribe([name])"><?php echo __('Describe'); ?></a></li>
	<li><a href="showCreateCmd('table', [name])"><?php echo __('Show create command'); ?></a></li>
	<li><a href="tableViewData([name])"><?php echo __('View data'); ?></a></li>
	<li><a href="javascript:void(0)"><?php echo __('Alter Table');?> &raquo;</a>
		<ul>
			<li><a href="tableAlter([name])"><?php echo __('Structure'); ?></a></li>
			<li class="option mysql4 mysql5 mysqli"><a href="tableIndexes([name])"><?php echo __('Indexes'); ?></a></li>
			<li class="option mysql4 mysql5 mysqli"><a href="tableEngine([name])"><?php echo __('Engine Type'); ?></a></li>
		</ul>
	</li>
	<li><a href="javascript:void(0)"><?php echo __('More operations'); ?> &raquo;</a>
		<ul>
			<li><a class="itrunc" href="objTruncate('table', [name])"><?php echo __('Truncate'); ?></a></li>
			<li><a class="idrop" href="objDrop('table', [name])"><?php echo __('Drop'); ?></a></li>
			<li><a class="iren" href="objRename('table', [name])"><?php echo __('Rename'); ?></a></li>
			<li><a class="icopy" href="objCopy('table', [name])"><?php echo __('Create Copy'); ?></a></li>
		</ul>
	</li>
	<li class="separator">-------------------------------------------------------</li>
	<li><a class="iexprt" href="tableExport([name])"><?php echo __('Export table data'); ?></a></li>
	<li><a class="itable" href="tableCreate()"><?php echo __('Create Table'); ?></a></li>
</ul>
</div>

<div id="view-menu">
<ul class="dropdown context">
	<li><a href="tableSelect([name])"><?php echo __('Select statement'); ?></a></li>
	<li class="option mysql4 mysql5 mysqli"><a href="tableDescribe([name])"><?php echo __('Describe'); ?></a></li>
	<li><a href="showCreateCmd('view', [name])"><?php echo __('Show create command'); ?></a></li>
	<li><a href="tableViewData([name])"><?php echo __('View data'); ?></a></li>
	<li><a href="objCreate(1)"><?php echo __('Create View'); ?></a></li>
	<li><a href="javascript:void(0)"><?php echo __('More operations'); ?> &raquo;</a>
		<ul>
			<li><a href="objDrop('view', [name])"><?php echo __('Drop'); ?></a></li>
			<li><a href="objRename('view', [name])"><?php echo __('Rename'); ?></a></li>
			<li><a class="icopy" href="objCopy('view', [name])"><?php echo __('Create Copy'); ?></a></li>
		</ul>
	</li>
</ul>
</div>

<div id="proc-menu">
<ul class="dropdown context">
	<li><a href="showCreateCmd('procedure', [name])"><?php echo __('Show create command'); ?></a></li>
	<li><a href="objCreate(2)"><?php echo __('Create Procedure'); ?></a></li>
	<li><a href="javascript:void(0)"><?php echo __('More operations'); ?> &raquo;</a>
		<ul>
			<li><a href="objDrop('procedure', [name])"><?php echo __('Drop'); ?></a></li>
			<li><a href="objRename('procedure', [name])"><?php echo __('Rename'); ?></a></li>
			<li><a class="icopy" href="objCopy('procedure', [name])"><?php echo __('Create Copy'); ?></a></li>
		</ul>
	</li>
</ul>
</div>

<div id="func-menu">
<ul class="dropdown context">
	<li><a href="showCreateCmd('function', [name])"><?php echo __('Show create command'); ?></a></li>
	<li><a href="objCreate(3)"><?php echo __('Create Function'); ?></a></li>
	<li><a href="javascript:void(0)"><?php echo __('More operations'); ?> &raquo;</a>
		<ul>
			<li><a href="objDrop('function', [name])"><?php echo __('Drop'); ?></a></li>
			<li><a href="objRename('function', [name])"><?php echo __('Rename'); ?></a></li>
			<li><a class="icopy" href="objCopy('function', [name])"><?php echo __('Create Copy'); ?></a></li>
		</ul>
	</li>
</ul>
</div>

<div id="trig-menu">
<ul class="dropdown context">
	<li><a href="showCreateCmd('trigger', [name])"><?php echo __('Show create command'); ?></a></li>
	<li><a href="objCreate(4)"><?php echo __('Create Trigger'); ?></a></li>
	<li><a href="javascript:void(0)"><?php echo __('More operations'); ?> &raquo;</a>
		<ul>
			<li><a href="objDrop('trigger', [name])"><?php echo __('Drop'); ?></a></li>
		</ul>
	</li>
</ul>
</div>

<div id="evt-menu">
<ul class="dropdown context">
	<li><a href="showCreateCmd('event', [name])"><?php echo __('Show create command'); ?></a></li>
	<li><a href="objCreate(5)"><?php echo __('Create Event'); ?></a></li>
	<li><a href="javascript:void(0)"><?php echo __('More operations'); ?> &raquo;</a>
		<ul>
			<li><a href="objDrop('event', [name])"><?php echo __('Drop'); ?></a></li>
			<!--li><a href="objRename('event', [name])"><?php echo __('Rename'); ?></a></li-->
		</ul>
	</li>
</ul>
</div>

<div id="panel-header">
<ul class="dropdown context">
	<li><a href="main_layout.toggle('north')"><?php echo __('Show/Hide Header'); ?></a></li>
</ul>
</div>

<div id="panel-menu-objects">
<ul class="dropdown context">
	<li><a href="main_layout.toggle('west')"><?php echo __('Show/Hide Panel'); ?></a></li>
</ul>
</div>

<div id="panel-menu-editor">
<ul class="dropdown context">
	<li><a href="data_layout.toggle('south')"><?php echo __('Show/Hide Panel'); ?></a></li>
</ul>
</div>

<div id="history-menu">
<ul class="dropdown context">
	<li class="clipboard single"><a href="javascript:void(0)" title="<?php echo __('Copy to clipboard'); ?>"><?php echo __('Copy to clipboard'); ?></a></li>
	<li class="clipboard"><a href="javascript:void(0)" title="<?php echo __('Copy all queries to clipboard'); ?>"><?php echo __('Copy all queries to clipboard'); ?></a></li>
	<li><a href="javascript:historyClear($(this))" title="<?php echo __('Clear all queries from history'); ?>"><?php echo __('Clear history'); ?></a></li>
</ul>
</div>

<div id="data-menu-th">
<ul class="dropdown context">
	<li><a href="copyColumn([name])"><?php echo __('Copy Column values'); ?></a></li>
</ul>
</div>

<div id="data-menu-td">
<ul class="dropdown context">
	<li><a href="copyText([name])"><?php echo __('Copy to clipboard'); ?></a></li>
	<li><a href="sqlFilterText([name])"><?php echo __('Generate SQL Filter'); ?></a></li>
</ul>
</div>

</div>