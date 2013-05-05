/*
    http://mywebsql.net/license
*/
var db_pgsql={quote:function(a){return-1==a.indexOf(".")?'"'+a+'"':'"'+a.replace(".",'"."')+'"'},escape:function(a){return"'"+a.replace(/\'/g,"''")+"'"}};
