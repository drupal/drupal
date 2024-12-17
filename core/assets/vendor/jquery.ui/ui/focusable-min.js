/*!
 * jQuery UI Focusable 1.14.1
 * https://jqueryui.com
 *
 * Copyright OpenJS Foundation and other contributors
 * Released under the MIT license.
 * https://jquery.org/license
 */
!function(e){"use strict";"function"==typeof define&&define.amd?define(["jquery","./version"],e):e(jQuery)}((function(e){"use strict";return e.ui.focusable=function(t,i){var s,n,u,a,o,r=t.nodeName.toLowerCase();return"area"===r?(n=(s=t.parentNode).name,!(!t.href||!n||"map"!==s.nodeName.toLowerCase())&&((u=e("img[usemap='#"+n+"']")).length>0&&u.is(":visible"))):(/^(input|select|textarea|button|object)$/.test(r)?(a=!t.disabled)&&(o=e(t).closest("fieldset")[0])&&(a=!o.disabled):a="a"===r&&t.href||i,a&&e(t).is(":visible")&&"visible"===e(t).css("visibility"))},e.extend(e.expr.pseudos,{focusable:function(t){return e.ui.focusable(t,null!=e.attr(t,"tabindex"))}}),e.ui.focusable}));
//# sourceMappingURL=focusable-min.js.map