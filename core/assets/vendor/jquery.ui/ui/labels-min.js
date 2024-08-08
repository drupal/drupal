/*!
 * jQuery UI Labels 1.14.0
 * https://jqueryui.com
 *
 * Copyright OpenJS Foundation and other contributors
 * Released under the MIT license.
 * https://jquery.org/license
 */
!function(t){"use strict";"function"==typeof define&&define.amd?define(["jquery","./version"],t):t(jQuery)}((function(t){"use strict";return t.fn.labels=function(){var t,s,e,i,n;return this.length?this[0].labels&&this[0].labels.length?this.pushStack(this[0].labels):(i=this.eq(0).parents("label"),(e=this.attr("id"))&&(n=(t=this.eq(0).parents().last()).add(t.length?t.siblings():this.siblings()),s="label[for='"+CSS.escape(e)+"']",i=i.add(n.find(s).addBack(s))),this.pushStack(i)):this.pushStack([])}}));
//# sourceMappingURL=labels-min.js.map