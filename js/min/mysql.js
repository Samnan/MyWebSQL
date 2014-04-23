/*
  (c) 2008-2012 Samnan ur Rehman
 @web        http://mywebsql.net
 @license    http://mywebsql.net/license
*/
var db_mysql={quote:function(a){return-1==a.indexOf(".")?"`"+a+"`":"`"+a.replace(".","`.`")+"`"},escape:function(a){a=a.replace(/\\/g,"\\\\");return'"'+a.replace(/\"/g,'\\"')+'"'}};
