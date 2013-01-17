/*
 Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 For licensing, see LICENSE.html or http://ckeditor.com/license
*/
(function(){function b(a,b){var c=a.lang.placeholder,d=a.lang.common.generalTab;return{title:c.title,minWidth:300,minHeight:80,contents:[{id:"info",label:d,title:d,elements:[{id:"text",type:"text",style:"width: 100%;",label:c.text,"default":"",required:!0,validate:CKEDITOR.dialog.validate.notEmpty(c.textMissing),setup:function(a){b&&this.setValue(a.getText().slice(2,-2))},commit:function(b){var c="[["+this.getValue()+"]]";CKEDITOR.plugins.placeholder.createPlaceholder(a,b,c)}}]}],onShow:function(){b&&
(this._element=CKEDITOR.plugins.placeholder.getSelectedPlaceHolder(a));this.setupContent(this._element)},onOk:function(){this.commitContent(this._element);delete this._element}}}CKEDITOR.dialog.add("createplaceholder",function(a){return b(a)});CKEDITOR.dialog.add("editplaceholder",function(a){return b(a,1)})})();