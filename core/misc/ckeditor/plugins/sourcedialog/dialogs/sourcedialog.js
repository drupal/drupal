/*
 Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 For licensing, see LICENSE.html or http://ckeditor.com/license
*/
CKEDITOR.dialog.add("sourcedialog",function(a){var b=CKEDITOR.document.getWindow().getViewPaneSize(),d=Math.min(b.width-70,800),b=b.height/1.5,c;return{title:a.lang.sourcedialog.title,minWidth:100,minHeight:100,onShow:function(){this.setValueOf("main","data",c=a.getData())},onOk:function(){function b(e){var c=this;a.setData(e,function(){c.hide();var b=a.createRange();b.moveToElementEditStart(a.editable());b.select()})}return function(){var a=this.getValueOf("main","data").replace(/\r/g,"");if(a===
c)return!0;CKEDITOR.env.ie?CKEDITOR.tools.setTimeout(b,0,this,a):b.call(this,a);return!1}}(),contents:[{id:"main",label:a.lang.sourcedialog.title,elements:[{type:"textarea",type:"textarea",id:"data",dir:"ltr",inputStyle:"cursor:auto;width:"+d+"px;height:"+b+"px;tab-size:4;text-align:left;","class":"cke_source"}]}]}});