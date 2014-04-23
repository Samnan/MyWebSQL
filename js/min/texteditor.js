/*
  (c) 2008-2012 Samnan ur Rehman
 @web        http://mywebsql.net
 @license    http://mywebsql.net/license
*/
textEditor=function(a){this.textarea=$(a)};textEditor.prototype.focus=function(){return this.textarea.focus()};textEditor.prototype.getCode=function(a){return this.textarea.val()};textEditor.prototype.setCode=function(a){this.textarea.val(a)};textEditor.prototype.canHighlight=function(){return!1};textEditor.prototype.highlightSql=function(){return!1};textEditor.prototype.getSelection=function(a){return this.textarea.val()};
textEditor.prototype.jumpToLine=function(a){h=this.textarea.prop("scrollHeight");this.textarea.prop("scrollTop",h)};textEditor.prototype.lastLine=function(){return-1};
