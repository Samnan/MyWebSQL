/**
 * This file is a part of MyWebSQL package
 *
 * @file:      js/editable.js
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

var curEditField = null;         // current edited field
var fieldInfo = null;            // information about fields in record set
var selectedRow = null;
var selectedRowFields = null;
var selectedRowForeignKey = null;
var alterTable = false;
var editOptions = { sortable:false, highlight:false, selectable:true, editEvent:'click', editFunc:editTableCell };
var foreignfieldInfo = null;
var checkBoxCode = '<span class="check">' + __('Yes') + '</span>';
var deletedFields = [];
var deletedForeignFields = [];
var TablesForRefDB = [];
var ColumnsForRefTable = [];
var selectedTabIndex =-1;
/* editing related data */
// numeric, length required or accepted, list of values

var dataTypes = {
	'bigint':[1,1,0],
	'binary':[0,1,0],
	'bit':[0,0,0],
	'blob':[0,1,0],
	'bool':[0,0,0],
	'boolean':[0,0,0],
	'char':[0,1,0],
	'date':[0,0,0],
	'datetime':[0,0,0],
	'decimal':[1,1,0],
	'double':[1,1,0],
	'enum':[0,0,1],
	'float':[1,1,0],
	'int':[1,1,0],
	'longblob':[0,0,0],
	'longtext':[0,0,0],
	'mediumblob':[0,0,0],
	'mediumint':[1,1,0],
	'mediumtext':[0,0,0],
	'numeric':[1,1,0],
	'real':[1,1,0],
	'set':[0,0,1],
	'smallint':[1,1,0],
	'text':[0,1,0],
	'time':[0,0,0],
	'timestamp':[0,0,0],
	'tinyblob':[0,0,0],
	'tinyint':[1,1,0],
	'tinytext':[0,0,0],
	'varbinary':[0,1,0],
	'varchar':[0,1,0],
	'year':[0,1,0]
};

var fieldInfo = [
	{'id':'fname', 'type':'char',  'list':[], 'desc': __('Field Name')},
	{'id':'ftype', 'type':'list',  'list':dataTypes, 'desc': __('Field Datatype')},
	{'id':'flen',  'type':'num',   'list':[], 'desc': __('Maximum Length of field value')},
	{'id':'fval',  'type':'char',  'list':[], 'desc': __('Default value [Use quotes to specify string values]')},
	{'id':'fsign', 'type':'check', 'list':[], 'desc': __('Unsigned numbered field only')},
	{'id':'fzero', 'type':'check', 'list':[], 'desc': __('Pad field values with leading zeros')},
	{'id':'fpkey', 'type':'check', 'list':[], 'desc': __('Create Primary Index on this field')},
	{'id':'fauto', 'type':'check', 'list':[], 'desc': __('Field value is Auto Incremented')},
	{'id':'fnull', 'type':'check', 'list':[], 'desc': __('Disallow NULL values in Field')}
];

var foreignkeyRules = {
        "CASCADE":[1,1,0],
        "SET NULL":[1,1,0],
        "RESTRICT":[1,1,0],
        "NO ACTION": [1,1,0]
        };
    
function addField(n) {
       if(selectedTabIndex == 1)
       {
           addFieldForeignKey(n);
       }
       else
       {
            for (i=0; i<n; i++) {
                    rows = '<tr>';
                    for (j=0; j<fieldInfo.length; j++)
                            rows += '<td class="edit ' + fieldInfo[j].type + ' n"></td>'; // add new class 'n' to the field
                    if (selectedRow != null)
                            $('#table_grid tbody tr.ui-state-active').after(rows);
                    else
                            $('#table_grid tbody').append(rows);
            }
       }
}

function addFieldForeignKey(n) {
	for (i=0; i<n; i++) {
		rows = '<tr>';
		for (j=0; j<foreignfieldInfo.length; j++){
                    rows += '<td id = '+ foreignfieldInfo[j].id+i+' class="edit ' + foreignfieldInfo[j].type + ' n"></td>'; // add new class 'n' to the field
                } 
		if (selectedRow != null)
                {
                    TablesForRefDB.splice(selectedRow.rowIndex, 0, {});
                    ColumnsForRefTable.splice(selectedRow.rowIndex, 0, {})
                    $('#table_grid1 tbody tr.ui-state-active').after(rows);
                }
		else
                {
                    $('#table_grid1 tbody').append(rows);
                    TablesForRefDB.push({});
                    ColumnsForRefTable.push({});
                }
                
	}
       
}

function loadTable() {
	addField(rowInfo.length);
	for(i=0; i<rowInfo.length; i++) {
		info = rowInfo[i];
		row = $('#table_grid tbody tr').eq(i+1);
		// save original field name for retrieval, and set proper class for the loaded field
		row.removeClass('n').addClass('o').data('oname', info['fname']);
		row.find('td').each(function(index) {
			id = fieldInfo[index].id;
			type = fieldInfo[index].type;
			text = info[id];

			// setup list of values for the field
			if (type == 'list' && info.flist.length > 0) {
				$(this).data('listValues', info.flist);
				$(this).html('<span>' + text + '</span>');
				appendListEditor($(this));
			}
			else if (type == 'check')
				$(this).html( text == '1' ? checkBoxCode : '' );
			else
				$(this).text(text);
		});
	}
	deletedFields = [];
        deletedForeignFields =[];
}

function loadTableForeignkey()
{ 
    addFieldForeignKey(foreigninfo.length);
    for(i=0; i<foreigninfo.length; i++) {
		info = foreigninfo[i];
		row = $('#table_grid1 tbody tr').eq(i+1);
		// save original field name for retrieval, and set proper class for the loaded field
		row.removeClass('n').addClass('o').data('oname', info['fname']);
		row.find('td').each(function(index) {
			id = foreignfieldInfo[index].id;
			type = foreignfieldInfo[index].type;
			text = info[id];

			// setup list of values for the field
			if (type == 'list' && info.flist.length > 0) {
				$(this).data('listValues', info.flist);
				$(this).html('<span>' + text + '</span>');
				appendListEditor($(this));
			}
			else if (type == 'check')
				$(this).html( text == '1' ? checkBoxCode : '' );
			else
				$(this).text(text);
		});
	}
	deletedForeignFields = [];
}

// options same as data table
function setupGrid(id, opt) {
	opt.editEvent ? void(0) : opt.editEvent = 'dblclick';
	opt.editFunc ? void(0) : opt.editFunc = editTableCell;

	if (opt.sortable) {
		sorttable.DATE_RE = /^(\d\d?)[\/\.-](\d\d?)[\/\.-]((\d\d)?\d\d)$/;
		table = document.getElementById(id);
		sorttable.makeSortable(table);
	}

	//if (opt.highlight) {
		$('#'+id+' tbody tr').live('mouseenter', function() {
			$(this).addClass("ui-state-hover");
		});
		$('#'+id+' tbody tr').live('mouseleave', function() {
			$(this).removeClass("ui-state-hover");
		});
	//}

	if (opt.selectable) { 
            $('#'+id+' tbody tr').live('click', function() {
                    if (selectedRow != null)
                            $(selectedRow).removeClass("ui-state-active");
                    $(this).addClass("ui-state-active");
                    selectedRow = this;
		});
	}

	if (opt.editable) {
		editOptions = opt;
		$('#'+id+' td.edit').live(opt.editEvent, opt.editFunc);
	}
}

function editTableCell() {
	td = $(this);
	if (curEditField != null)
		closeEditor(true);

	span = td.find('span:first');
	txt = span.length ? span.text() : td.text();
	tstyle = "left";

	td.data('defText', txt).addClass('current');

	curEditField = this;
	index = td.index();
        
        type =td.closest('tr').closest('table')[0].id;
        if(type === 'table_grid')
            fi = getField(index);
        else
            fi = getforeignField(index);
        
	w = td.width();
	h = td.height();
	td.attr('width', w);

	setMessage(fi.desc);
	input = createCellEditor(td, fi, txt, w, h, tstyle);

	setTimeout( function() { input.focus(); }, 50 );
}

function closeEditor(type, upd, forward) {
	if (!curEditField)
		return false;

	txt = '';
	obj = $(curEditField);
	if (upd) {
		if (type == 'combo') {
			txt = obj.find('select').val();
			obj.html('<span>'+txt+'</span>');
		}
		else if (type == 'check') {
			txt = obj.find('input').prop('checked') ? checkBoxCode : '';
			obj.html(txt);
		} else {
			txt = obj.find('input').val();
			obj.text(txt);
		}
	} else
		txt = obj.data('defText');

	obj.removeClass('current');
	if (txt != obj.data('defText'))
		obj.parent().addClass('m'); // set modified flag for this field

	$(curEditField).removeAttr('width');
	curEditField = null;

	if (txt != '' && type == 'combo' && obj.index() == 1 && obj.parent().parent().parent()[0].id == 'table_grid') {
		typeInfo = dataTypes[txt];

		// show or hide field length based on the data type selected
		fieldLengthTd = obj.parent().find('td').eq(2);
		fieldLength = fieldLengthTd.text();
		if (typeInfo[1] == 0 && fieldLength != '') {
			fieldLengthTd.data('fLength', fieldLength).text('');
		} else if (typeInfo[1] == 1) {
			fieldLength = fieldLengthTd.data('fLength');
			fieldLengthTd.text(fieldLength); // restore previous length (if any)
		}

		// change interface so that 'list of values' can be entered by user for enum and set fields
		// also only if user is moving forward while editing, since this behaviour is not desired on shift+TAB
		if (typeInfo[2] == 1) {
			appendListEditor(obj);
			if (forward) { // moving forward, edit list of values
				setTimeout(function() {editListOfValues(obj);}, 50);
				return false;
			}
		}
	}

	return true;
}

function checkEditField(event, type) {
	combo = $(this).is('select');
	check = $(this).attr('type') == 'checkbox';
	tag = combo ? 'combo' : (check ? 'check' : 'input');

	keys = combo ? [13,9] : [13,9,38,40] ;  // for select, up down arrow keys work differently
	if (keys.indexOf(event.keyCode) != -1) {
		event.preventDefault();
		elem = false;
		if (event.keyCode == 9) {
			elem = event.shiftKey ? $(curEditField).prev('.edit') : $(curEditField).next('.edit');
			if (!elem.length) {  // move to next/previous record if possible
				tr = event.shiftKey ? $(curEditField).parent().prev() : $(curEditField).parent().next();
				if (tr.length)
					elem = event.shiftKey ? tr.find('td:last') : tr.find('td:first');
			}
		} else if (event.keyCode == 38 || event.keyCode == 40) {
			tr = event.keyCode == 38 ? $(curEditField).parent().prev() : $(curEditField).parent().next();
			if (tr.length)
				elem = tr.find('td').eq($(curEditField).index());
		}
		moveNext = closeEditor(tag, true, !(event.keyCode == 9 && event.shiftKey));
		if (moveNext && elem && elem.length)    // edit next or previous element
			elem.trigger(editOptions.editEvent);
	} else if (!isCellEditable($(curEditField))) {
		setError($(this), __('This attribute is not required for selected field type'));
		event.preventDefault();
		return false;
	}
}

function createCellEditor(td, fi, txt, w, h, align) {
	keyEvent = 'keydown';
	input = null;
	code = '<form name="cell_editor_form" class="cell_editor_form" action="javascript:void(0);">';
	switch(fi['type']) {
		case 'list':
			code += '<select name="cell_editor" class="cell_combo" style="text-align:' + align + ';width: ' + (w+5) + 'px;">';
			code += '<option value=""></option>';
                        var filist = fi['list'];
                        if(selectedTabIndex =1)
                        {
                             var CurrentRowIndex = td.parent().parent().children().index(td.parent());
                             var col = td.parent().children().index(td);
                             if(col == 3) 
                             {
                                filist = TablesForRefDB[CurrentRowIndex-1];
                             }
                             else if(col == 4) 
                             {
                                filist = ColumnsForRefTable[CurrentRowIndex-1];
                             }
                        }
			for(opt in filist) {
				sel = (txt == opt) ? ' selected="selected"' : '';
				opt = str_replace('"','&quot;', opt);
				code += '<option value="'+opt+'"'+sel+'>'+opt+'</option>';
			}
			code += '</select>';
			break;
		case 'check':
			code += '<input type="checkbox" name="cell_editor" class="cell_check" style="text-align:' + align + '"';
			if (txt != '')
				code += ' checked="checked" ';
			code += '/>';
			break;
		default:
			code += '<input type="text" name="cell_editor" class="cell_editor" style="text-align:' + align + ';width: ' + w + 'px;" />';
			break;
	}
	code += '</form>';
	td.html(code);

	switch(fi['type']) {
		case 'list':
			input = td.find('select');
			input.bind(keyEvent, checkEditField ).blur( function() {
				closeEditor('combo', true)
			}).bind('click', function(e) {
				e.stopPropagation();
			});
			break;
		case 'check':
			input = td.find('input');
			input.bind(keyEvent, checkEditField ).blur( function() {
				closeEditor('check', true)
			}).bind('click', function(e) {
				e.stopPropagation();
				//$(this).next().html($(this).attr('checked') ? 'Yes' : 'No');
			});
			break;
		default:
			input = td.find('input');
			input.val(txt).select().bind(keyEvent, checkEditField ).blur( function() {
				closeEditor('text', true)
			}).bind('click', function(e) {
				e.stopPropagation();
			});
			break;
	}
	return input;
}

function getforeignField(n) {
	return foreignfieldInfo[n];
}

function getField(n) {
	return fieldInfo[n];
}

function isCellEditable(cell) {
	col = cell.index();
	if (col < 2)
		return true;	// first two cells are always editable

	row = cell.parent();
	name = row.find('td').eq(0).text();
	td = row.find('td').eq(1);
	span = td.find('span:first');
	type = span.length ? span.text() : td.text();

	if (name == '' || type == '')
		return false;

	if (!dataTypes[type])
		return false;

	typeInfo = dataTypes[type];

	if (col == 2 && typeInfo[1] == 0)
		return false;

	return true;
}

/* ****** */

function deleteField()
{
	if ($('#table_grid tbody tr').length < 3)
	{
		setError(null, __('Table information requires at least one valid field'));
		return;
	}

	if ($(selectedRow).length) {
		if ($(selectedRow).hasClass('o')){
                    if(td.closest('tr').closest('table')[0].id == 'table_grid')
                        deletedFields.push($(selectedRow).data('oname')); // push original name onto deleted list
                    else if(td.closest('tr').closest('table')[0].id == 'table_grid1')
                        deletedForeignFields.push($(selectedRow).data('oname')); // push original name onto deleted list
                }
                    
		$(selectedRow).remove();
		setMessage('Field deleted');
	}
	selectedRow = null;
        if(selectedTabIndex ===0)
            selectedRowFields = null;
        else if(selectedTabIndex ===1)
            selectedRowForeignKey = null;
}

function editListOfValues(obj)
{
	$('#dialog-list').data('attachField', obj);
	$('#dialog-list').dialog('open');
}

function validateTableInfo() {

	if ($('#table-name').val() == '') {
		setError('#table-name', __('Table name is required'));
		return false;
	}

	errors = 0;
	errorFields = [];

	$('#table_grid tbody tr:gt(0)').each(function() {
		$(this).removeClass('x');
		$(this).children('td').each(function(index) {
			if ($(this).text() != '') {
				$(this).parent().addClass('x');
				return false;
			}
		});
	});
        
	$('#table_grid1 tbody tr:gt(0)').each(function() { 
                isForeignColumnHasValue =false;
                $(this).children('td').each(function() {
                        if($(this).text() == '')
                        {
                            if (isForeignColumnHasValue) { 
                                errorFields[errors++] = $(this).parent();
                                return false;
                            } 
                        }
                        else
                        {
                                 isForeignColumnHasValue = true;
                        }

		});
	});

	$('#table_grid tbody tr.x').each(function() {
		// @todo: find empty rows in between filled ones
		$(this).children('td').each(function(index) {
			if ((index == 0 || index == 1) && $(this).text() == '') errorFields[errors++] = $(this).parent();
		});
	});

	numFields = $('#table_grid tbody tr.x').length;

	if (errors)
		setError(errorFields, __('One or more field information is incomplete'));
	else if (numFields == 0)
		setError(null, __('Table information requires at least one valid field'));
	else
		submitTableInfo(numFields);
}

function submitTableInfo(n) {
	numFields = 0;
	fields = [];
	$('#table_grid tbody tr.x').each(function() {
		row = {};
		if ($(this).hasClass('o'))
			row.fstate = $(this).hasClass('m') ? 'change' : 'old';
		else
			row.fstate = 'new';
		row.oname = $(this).data('oname');
		$(this).children('td').each(function(index) {
			id = fieldInfo[index].id;
			span = $(this).find('span:first');
			row[id] = span.length ? span.text() : $(this).text();
			if (fieldInfo[index].type == 'list' && span.length)
				row.flist = $(this).data('listValues');
		});
		fields[numFields++] = row;
	});
        
        numFields = 0;
        foreignfields = [];
        $('#table_grid1 tbody tr:gt(0)').each(function() {
		row = {};
		if ($(this).hasClass('o'))
			row.fstate = $(this).hasClass('m') ? 'change' : 'old';
		else
			row.fstate = 'new';
		row.oname = $(this).data('oname');
		$(this).children('td').each(function(index) {
			id = foreignfieldInfo[index].id;
			span = $(this).find('span:first');
			row[id] = span.length ? span.text() : $(this).text(); 
		});
		foreignfields[numFields++] = row;
	});

	json = {	"name": $('#table-name').val() };
	json.props = {
		"engine": $('#enginetype').val(),
		"charset": $('#charset').val(),
		"collation": $('#collation').val(),
		"comment": $('#comment').val()
	};
	json.fields = fields;
        json.foreignfields = foreignfields;
	json.delfields = deletedFields;
        json.deletedForeignFields = deletedForeignFields;

	query = JSON.stringify(json);

	setMessage('Please wait...');
	page = alterTable ? 'altertbl' : 'createtbl';
	command = alterTable ? 'alter' : 'create';

	wrkfrmSubmit(page, command, '', query, responseHandler);
}

function clearTableInfo() {
	optionsConfirm(__('Are you sure you want to clear all field information from table?'), 'grid.clear', function(result, id, confirm_always) {
		if (result) {
			if (confirm_always)
				optionsConfirmSave(id);
			$('#table_grid tbody td').html('').removeData('listValues');
			$('#enginetype').val('');
			$('#charset').val('');
			$('#collation').val('');
			$('#comment').val('');
			setMessage('Field information cleared');
		}
	});
}

function responseHandler(data) {
	result = $(data).find('#result').text();
	message = $(data).find('#message').html();
	if (result == '1') {
		setMessage(alterTable ? __('Table successfully modified') : __('Table successfully created'));
		$('#tab-messages').html(message);
		// mark all fields as 'old' so that alter command can be used further on the same grid
		$('#table_grid tbody tr.x').removeClass('x m n').addClass('o');
		deletedFields = [];
                deletedForeignFields = [];
		if (!alterTable)
			parent.objectsRefresh();
	}
	else {
		setMessage('Error');
		$('#tab-messages').html(message);
	}
	$("#grid-tabs").tabs('select', 2);
	div = $('#tab-messages div.sql_text').length > 0 ? $('#tab-messages div.sql_text') : $('#tab-messages div.sql_error');
	if (div.length) {
		code = div.html2txt();
		obj_lines = $('<div class="sql_lines"></div>');
		obj_out = $('<pre class="sql_output"></pre>');
		div.html('').append(obj_lines).append(obj_out);
		parent.commandEditor.win.highlightSql($('#tab-messages pre.sql_output'), $('#tab-messages div.sql_lines'), code);
	}

	$('#popup_overlay').addClass('ui-helper-hidden');
}

function setupEditable(alter) {
	alterTable = alter;
	$('#grid-tabs').tabs({
		select: function(event, ui) {
			// if altering tables, clear field information button is always hidden
			btn = alterTable ? '#btn_add, #btn_del' : '#btn_add, #btn_del, #btn_clear';
                        selectedTabIndex = ui.index; 
			if (ui.index == 0) {
				$(btn).show();
                                selectedRowForeignKey = selectedRow;
                                selectedRow = selectedRowFields;
			}
                        else if(ui.index == 1){
                                $(btn).show(); 
                                selectedRowFields = selectedRow;
                                selectedRow = selectedRowForeignKey;
			}
			else
				$(btn).hide();
		}
	});

	$("#dialog-list").dialog({
		autoOpen: false,
		width:240,
		height: 240,
		modal: true,
		draggable: false,
		resizable: false,
		open: loadDialogValues,
		buttons: {
			'Add': addListValue,
			'Delete': function() {
				$('#list-items option:selected').remove();
				setTimeout(function() {$('#list-items').focus();}, 10);
			},
			'Done': saveDialogValues
		}
	});

        var columnNames ={};
        rowInfo.forEach(function(row)
        {
            columnNames[row.fname]='1.0.0'
        });
        
        var dbListObject={}; 
        dbList.forEach(function(row)
        {
            dbListObject[row]='1.0.0'
        });
         
        foreignfieldInfo = [
                {'id':'fname', 'type':'char',  'list':[], 'desc': __('Foreign key Name(unique)')},
                {'id':'fcolumn', 'type':'list',  'list':columnNames, 'desc': __('Select a column')},
                {'id':'fDB',  'type':'list',   'list':dbListObject, 'desc': __('Select a database')},
                {'id':'freftable',  'type':'list',  'list':TablesForRefDB, 'desc': __('To which table you want to set foreign key')},
                {'id':'freftablecol', 'type':'list', 'list':ColumnsForRefTable, 'desc': __('Select column that has to be used for foreign key')},
                {'id':'fdelete', 'type':'list', 'list':foreignkeyRules, 'desc': __('The effect for delete operation on parent table')},
                {'id':'fupdate', 'type':'list', 'list':foreignkeyRules, 'desc': __('The effect for update operation on parent table')} 
        ];
        
	setupGrid('table_grid', {selectable:true,editable:true,editEvent:'click',editFunc:editTableCell});
	setupGrid('table_grid1', {selectable:true,editable:true,editEvent:'click',editFunc:editTableCell});
	
	if (alter) {
		$('#table-name').attr('disabled', true);
		loadTable();
                loadTableForeignkey();
		//setTimeout(function() { $('#table_grid').find('td').eq(0).trigger('click'); }, 200 );
	}
	else {
		addField(10);
                addFieldForeignKey(0);
		$('#table-name').bind('keydown', function(e) {
			if (e.keyCode == 9) {
				e.preventDefault();
				$('#table_grid').find('td').eq(0).trigger('click');
			}
		});
		setTimeout(function() { $('#table-name').focus(); }, 50 );
	}

	$('#dialog-list #item').keydown(function(e) {
		if (e.keyCode == 13)
			addListValue();
	});

	$('#btn_add').button().click(function() { addField(1); });
	$('#btn_del').button().click(function() { deleteField(); });
	$('#btn_submit').button().click(function() { validateTableInfo(); });
	alterTable ? $('#btn_clear').hide() : $('#btn_clear').button().click(function() { clearTableInfo(); });
        
        $('#fDB0').live('change', function(){ 
            var dbName = $(this).find(".cell_combo").val() == undefined ? $(this)[0].innerHTML :$(this).find(".cell_combo").val(); 
            var CurrentRowIndex = $(this).parent().parent().children().index($(this).parent());
            for (var member in TablesForRefDB[CurrentRowIndex-1]) delete TablesForRefDB[CurrentRowIndex-1][member]; 
            if($(this).find(".cell_combo").val() != undefined)
            {
                selectedRow.childNodes[3].innerHTML="";      //clear table cell
                selectedRow.childNodes[4].innerHTML="";      //clear table column value
            }
            
            $.ajax({
                type: "POST",
                url: 'AjaxHelper.php', 
                data: { func: 'getTables',  dbName : dbName },
                success: function(json) { 
                      $.each(json, function(i, value) {
                             TablesForRefDB[CurrentRowIndex-1][value] = '1.0.0';
                      });
                }
            });  
         });
        
        $('#freftable0').live('change', function(){ 
           var dbName = $(this).parent()[0].childNodes[2].textContent== undefined ? $(this).parent()[0].childNodes[2].innerHTML  :$(this).parent()[0].childNodes[2].textContent;
           var tableName = $(this).find(".cell_combo").val() == undefined ?  $(this)[0].innerHTML  :$(this).find(".cell_combo").val();
            var CurrentRowIndex = $(this).parent().parent().children().index($(this).parent());
            for (var member in ColumnsForRefTable[CurrentRowIndex-1]) delete ColumnsForRefTable[CurrentRowIndex-1][member]; 
            if($(this).find(".cell_combo").val()  != undefined)
            {
                selectedRow.childNodes[4].innerHTML="";      //clear table column value
            }
            $.ajax({
               type: "POST",
               url: 'AjaxHelper.php', 
               data: { func: 'getTableFields', dbName : dbName, tablename : tableName },
               success: function(json) { 
                     $.each(json, function(i, value) {
                            ColumnsForRefTable[CurrentRowIndex-1][value] = '1.0.0';
                     });
               }
           });  
        });
        
        if(alter)
        {
            $('#fDB0').change();
            $('#freftable0').change();
        }
}

function setError(o, s) {
	$('#grid-messages').html(s).addClass('error');
	if ($(selectedRow).length) {
		$(selectedRow).removeClass("ui-state-active");
		selectedRow = null;
	}
	$(o).each(function() { $(this).addClass('error'); });
	setTimeout(function() { $(o).each(function() { $(this).removeClass('error'); }); }, 2000);
}

function setMessage(s) {
	$('#grid-messages').html(s).removeClass('error');
}

/* dialog related functions */

function loadDialogValues(e, ui) {
	$('#list-items').html('');
	obj = $(this).data('attachField');
	list = obj.data('listValues');
	$(list).each(function(index) {
		option = $('<option></option>').val(list[index]).text(list[index]);
		$('#list-items').append(option);
	});
	setTimeout(function() { $('#dialog-list #item').focus(); }, 50 );
}

function saveDialogValues(e, ui) {
	obj = $(this).data('attachField');
	list = [];
	$('#list-items option').each(function() {
		list.push($(this).val());
	});
	obj.data('listValues', list);
	// make next field editable again in the grid
	setTimeout(function() { obj.next().trigger('click'); }, 100 );
	$('#dialog-list').dialog('close');
}

function addListValue() {
	val = $('#item').val();
	if (val != '') {
		found = false;
		$('#list-items option').each(function() {
			if ($(this).val() == val) {
				found = true;
				return false;
			}
		});
		if (!found) {
			option = $('<option></option>').val(val).text(val);
			$('#list-items').append(option);
			$('#item').val('').focus();
		}
	}
}

function appendListEditor(obj) {
	obj.append(
		$('<span title="Edit list of values for this field" class="list">&nbsp;</span>')
			.click(function(e) {
				e.stopPropagation();
				editListOfValues($(this).parent());
			})
	);
}