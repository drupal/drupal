/*! jQuery UI - v1.12.1 - 2017-03-31
* http://jqueryui.com
* Copyright jQuery Foundation and other contributors; Licensed  */
!function(a){"function"==typeof define&&define.amd?define(["jquery","../version","../effect"],a):a(jQuery)}(function(a){return a.effects.define("fade","toggle",function(b,c){var d="show"===b.mode;a(this).css("opacity",d?0:1).animate({opacity:d?1:0},{queue:!1,duration:b.duration,easing:b.easing,complete:c})})});