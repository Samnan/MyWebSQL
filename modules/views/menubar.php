<div class="ui-state-default ui-corner-all ui-helper-clearfix">
	<ul id="main-menu" class="dropdown">
		<li>
			<a href="javascript:void(0)"><?php echo __('Database'); ?></a>
			<ul class="ui-state-default">
				<li class="db"><a class="irfrsh" href="javascript:objectsRefresh()" title="<?php echo __('Refresh database object list'); ?>"><?php echo __('Refresh'); ?></a></li>
				<li><a href="javascript:dbCreate()" title="<?php echo __('Create a new database'); ?>"><?php echo __('Create new'); ?>...</a></li>
				<li class="db option mysql4 mysql5 mysqli sqlite"><a class="ibatch" href="javascript:dbBatch()" title="<?php echo __('Perform one or more batch operations on database'); ?>"><?php echo __('Batch operations'); ?>...</a></li>
				<li class="db option mysql4 mysql5 mysqli sqlite"><a class="iexpdb" href="javascript:dataExport()" title="<?php echo __('Export database to external file'); ?>"><?php echo __('Export'); ?>...</a></li>
			</ul>
		</li>
		<li class="db">
			<a href="javascript:void(0)"><?php echo __('Objects'); ?></a>
			<ul class="ui-state-default">
				<li><a class="itable" href="javascript:tableCreate()" title="<?php echo __('Create a new table in the database'); ?>"><?php echo __('Create Table'); ?>...</a></li>
				<li class="option mysql5 mysqli sqlite"><a class="iview" href="javascript:objCreate(1)" title="<?php echo __('Create a new view in the database'); ?>"><?php echo __('Create View'); ?>...</a></li>
				<li class="option mysql5 mysqli"><a class="iproc" href="javascript:objCreate(2)" title="<?php echo __('Create a new stored procedure in the database'); ?>"><?php echo __('Create Stored Procedure'); ?>...</a></li>
				<li class="option mysql5 mysqli"><a class="ifunc" href="javascript:objCreate(3)" title="<?php echo __('Create a new user defined function in the database'); ?>"><?php echo __('Create Function'); ?>...</a></li>
				<li class="option mysql5 mysqli sqlite"><a class="itrig" href="javascript:objCreate(4)" title="<?php echo __('Create a new trigger in the database'); ?>"><?php echo __('Create Trigger'); ?>...</a></li>
				<li class="option mysql5 mysqli"><a class="ievt" href="javascript:objCreate(5)" title="<?php echo __('Create a new event in the database'); ?>"><?php echo __('Create Event'); ?>...</a></li>
			</ul>
		</li>

		<li>
			<a href="javascript:void(0)"><?php echo __('Data'); ?></a>
			<ul class="ui-state-default">
				<li class="option mysql4 mysql5 mysqli sqlite"><a class="iimprt" href="javascript:dataImport()" title="<?php echo __('Import multiple queries from batch file'); ?>"><?php echo __('Import batch file'); ?>...</a></li>
				<li class="option mysql4 mysql5 mysqli sqlite"><a class="iimprt" href="javascript:tableImport()" title="<?php echo __('Import table data from external file'); ?>"><?php echo __('Import table data'); ?>...</a></li>
				<li class="db option mysql4 mysql5 mysqli sqlite"><a class="iexpdb" href="javascript:dataExport()" title="<?php echo __('Export database to batch file as sql dump'); ?>"><?php echo __('Export database'); ?>...</a></li>
				<li class="db"><a class="iexprt" href="javascript:resultsExport()" title="<?php echo __('Export query results to clipboard or files'); ?>"><?php echo __('Export current results'); ?>...</a></li>
			</ul>
		</li>

		<li>
			<a href="javascript:void(0)"><?php echo __('Tools'); ?></a>
			<ul class="ui-state-default">
				<li class="option mysql4 mysql5 mysqli"><a class="itprc" href="javascript:toolsProcManager()" title="<?php echo __('View and manage database processes'); ?>"><?php echo __('Process Manager'); ?></a></li>
				<li class="option mysql4 mysql5 mysqli"><a class="itusr" href="javascript:toolsUsers()" title="<?php echo __('Manage database users'); ?>"><?php echo __('User Manager'); ?></a></li>
				<li class="db option mysql4 mysql5 mysqli"><a class="itchk" href="javascript:toolsDbCheck()" title="<?php echo __('Analyze and repair database tables'); ?>"><?php echo __('Repair Tables'); ?></a></li>
				<li class="db"><a class="itsrch" href="javascript:toolsDbSearch()" title="<?php echo __('Search for text in the database'); ?>"><?php echo __('Search in Database'); ?></a></li>
			</ul>
		</li>

		<li>
			<a href="javascript:void(0)"><?php echo __('Information'); ?></a>
			<ul class="ui-state-default">
				<li class="option mysql4 mysql5 mysqli"><a href="javascript:infoServer()" title="<?php echo __('View mysql server and connection details'); ?>"><?php echo __('Server/Connection Details'); ?></a></li>
				<li class="option mysql4 mysql5 mysqli"><a href="javascript:infoVariables()" title="<?php echo __('View mysql server variables'); ?>"><?php echo __('Server Variables'); ?></a></li>
				<li class="db"><a href="javascript:infoDatabase()" title="<?php echo __('View current database summary stats'); ?>"><?php echo __('Database Summary'); ?></a></li>
			</ul>
		</li>

		<li>
			<a href="javascript:void(0)"><?php echo __('Interface'); ?></a>
			<ul>
				<!--li><a href="javascript:toolsOptions()" title="Configure application options">Options</a></li-->
				<li><a href="javascript:void(0)"><?php echo __('UI Theme'); ?></a>
					<ul class="ui-state-default">
						{{THEMES_MENU}}
					</ul>
				</li>
				<li><a href="javascript:void(0)"><?php echo __('Language'); ?></a>
					<ul id="menu-language" class="ui-state-default">
						{{LANGUAGE_MENU}}
					</ul>
				</li>
				<li><a href="javascript:void(0)"><?php echo __('SQL Editor'); ?></a>
					<ul class="ui-state-default">
						{{EDITOR_MENU}}
					</ul>
				</li>
				<li><a href="javascript:void(0)"><?php echo __('Show/Hide Panel'); ?></a>
					<ul class="ui-state-default">
						<li><a href="javascript:main_layout.toggle('west')" title="<?php echo __('Toggle Object Viewer'); ?>"><?php echo __('Database Objects'); ?></a></li>
						<li><a href="javascript:data_layout.toggle('south')" title="<?php echo __('Toggle Sql Editor'); ?>"><?php echo __('Sql Editor'); ?></a></li>
					</ul>
				</li>
			</ul>
		</li>

		<li>
			<a href="javascript:void(0)"><?php echo __('Help'); ?></a>
			<ul class="ui-state-default">
				<li><a class="ihlp" href="javascript:helpShowAll()" title="<?php echo __('Learn the basics of using MyWebSQL'); ?>"><?php echo __('Help contents'); ?></a></li>
				<li class="db"><a class="itutor" href="javascript:helpQuickTutorial()" title="<?php echo __('See quick hands-on tutorial of MyWebSQL interface'); ?>"><?php echo __('QuickStart Tutorials'); ?></a></li>
				<li><a class="idocs" href="javascript:helpOnlineDocs()" title="<?php echo __('View online documentation on project website'); ?>"><?php echo __('Online documentation'); ?></a></li>
				<li><a class="iftr" href="javascript:helpRequestFeature()" title="<?php echo __('If you would like your most favourite feature to be part of MyWebSQL, please click here to inform about it'); ?>"><?php echo __('Request a Feature'); ?>...</a></li>
				<li><a class="ibug" href="javascript:helpReportBug()" title="<?php echo __('If you have found a problem, or having trouble using the application, please click here to report the problem'); ?>"><?php echo __('Report a Problem'); ?></a></li>
				<li><a href="javascript:helpCheckUpdates()" title="<?php echo __('Check for updated versions of the application online'); ?>"><?php echo __('Check for updates'); ?></a></li>
			</ul>
		</li>

		<li class="right"><a class="ilgout" href="javascript:logout()" title="<?php echo __('Logout from this session'); ?>"><?php echo __('Logout'); ?></a></li>
	</ul>
</div>