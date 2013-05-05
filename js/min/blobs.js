/*
    http://mywebsql.net/license
*/
function blobChangeType(){id=document.frmquery.id.value;name=document.frmquery.name.value;query=document.frmquery.query.value;x=document.frmquery.blobtype.selectedIndex;wrkfrmSubmit("viewblob",id,name,query)}function blobSave(){id=document.frmquery.id.value;name=document.frmquery.name.value;query=document.frmquery.query.value;document.frmquery.act.value="save";wrkfrmSubmit("viewblob",id,name,query)};
