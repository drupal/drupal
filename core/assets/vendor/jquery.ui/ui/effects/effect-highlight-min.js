/*! jQuery UI - v1.12.1 - 2017-03-31
* http://jqueryui.com
* Copyright jQuery Foundation and other contributors; Licensed  */
!function(a){"function"==typeof define&&define.amd?define(["jquery","../version","../effect"],a):a(jQuery)}(function(a){return a.effects.define("highlight","show",function(b,c){var d=a(this),e={backgroundColor:d.css("backgroundColor")};"hide"===b.mode&&(e.opacity=0),a.effects.saveStyle(d),d.css({backgroundImage:"none",backgroundColor:b.color||"#ffff99"}).animate(e,{queue:!1,duration:b.duration,easing:b.easing,complete:c})})});