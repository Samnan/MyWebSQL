/*
  (c) 2008-2012 Samnan ur Rehman
 @web        http://mywebsql.net
 @license    http://mywebsql.net/license
*/
(function(b){b.widget("ui.combobox",{_create:function(){var h=this,e=this.element.hide(),d=e.children(":selected"),d=d.val()?d.text():"",c=b("<input>").insertAfter(e).val(d).autocomplete({delay:0,minLength:0,source:function(f,a){var c=RegExp(b.ui.autocomplete.escapeRegex(f.term),"i");a(e.children("option").map(function(){var a=b(this).text();if(this.value&&(!f.term||c.test(a)))return{label:a.replace(RegExp("(?![^&;]+;)(?!<[^<>]*)("+b.ui.autocomplete.escapeRegex(f.term)+")(?![^<>]*>)(?![^&;]+;)","gi"),
"<strong>$1</strong>"),value:a,option:this}}))},select:function(b,a){a.item.option.selected=!0;h._trigger("selected",b,{item:a.item.option})},change:function(c,a){if(!a.item){var d=RegExp("^"+b.ui.autocomplete.escapeRegex(b(this).val())+"$","i"),g=!1;e.children("option").each(function(){if(this.value.match(d))return this.selected=g=!0,!1});if(!g)return b(this).val(""),e.val(""),!1}}}).addClass("ui-widget ui-widget-content ui-corner-left");c.data("autocomplete")._renderItem=function(c,a){return b("<li></li>").data("item.autocomplete",
a).append("<a>"+a.label+"</a>").appendTo(c)};b("<button>&nbsp;</button>").attr("tabIndex",-1).attr("title","Show All Items").insertAfter(c).button({icons:{primary:"ui-icon-triangle-1-s"},text:!1}).removeClass("ui-corner-all").addClass("ui-corner-right ui-button-icon").click(function(){c.autocomplete("widget").is(":visible")?c.autocomplete("close"):(c.autocomplete("search",""),c.focus())})}})})(jQuery);
