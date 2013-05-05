/*
    http://mywebsql.net/license
*/
var db_sqlite={quote:function(a){return-1==a.indexOf(".")?"["+a+"]":"["+a.replace(".","].[")+"]"},escape:function(a){a=a.replace("\\","\\\\");return'"'+a.replace('"','\\"')+'"'}};
