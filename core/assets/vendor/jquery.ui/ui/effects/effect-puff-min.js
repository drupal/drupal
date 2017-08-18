/*! jQuery UI - v1.12.1 - 2017-03-31
* http://jqueryui.com
* Copyright jQuery Foundation and other contributors; Licensed  */
!function(a){"function"==typeof define&&define.amd?define(["jquery","../version","../effect","./effect-scale"],a):a(jQuery)}(function(a){return a.effects.define("puff","hide",function(b,c){var d=a.extend(!0,{},b,{fade:!0,percent:parseInt(b.percent,10)||150});a.effects.effect.scale.call(this,d,c)})});