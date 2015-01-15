/*!
 * jQuery UI Effects Clip 1.11.2
 * http://jqueryui.com
 *
 * Copyright 2014 jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/clip-effect/
 */(function(e){typeof define=="function"&&define.amd?define(["jquery","./effect"],e):e(jQuery)})(function(e){return e.effects.effect.clip=function(t,n){var r=e(this),i=["position","top","bottom","left","right","height","width"],s=e.effects.setMode(r,t.mode||"hide"),o=s==="show",u=t.direction||"vertical",a=u==="vertical",f=a?"height":"width",l=a?"top":"left",c={},h,p,d;e.effects.save(r,i),r.show(),h=e.effects.createWrapper(r).css({overflow:"hidden"}),p=r[0].tagName==="IMG"?h:r,d=p[f](),o&&(p.css(f,0),p.css(l,d/2)),c[f]=o?d:0,c[l]=o?0:d/2,p.animate(c,{queue:!1,duration:t.duration,easing:t.easing,complete:function(){o||r.hide(),e.effects.restore(r,i),e.effects.removeWrapper(r),n()}})}});