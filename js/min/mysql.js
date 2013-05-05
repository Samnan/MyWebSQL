/*
    http://mywebsql.net/license
*/
var db_mysql={quote:function(a){return-1==a.indexOf(".")?"`"+a+"`":"`"+a.replace(".","`.`")+"`"},escape:function(a){a=a.replace(/\\/g,"\\\\");return'"'+a.replace(/\"/g,'\\"')+'"'}};
