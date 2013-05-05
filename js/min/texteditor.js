/*
    http://mywebsql.net/license
*/
textEditor=function(a){this.textarea=$(a)};textEditor.prototype.focus=function(){return this.textarea.focus()};textEditor.prototype.getCode=function(){return this.textarea.val()};textEditor.prototype.setCode=function(a){this.textarea.val(a)};textEditor.prototype.canHighlight=function(){return!1};textEditor.prototype.highlightSql=function(){return!1};textEditor.prototype.getSelection=function(){return this.textarea.val()};
textEditor.prototype.jumpToLine=function(){h=this.textarea.prop("scrollHeight");this.textarea.prop("scrollTop",h)};textEditor.prototype.lastLine=function(){return-1};
