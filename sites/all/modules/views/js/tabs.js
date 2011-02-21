// $Id: tabs.js,v 1.4.4.4 2010/03/20 23:16:37 dereine Exp $

/**
 * @file tabs.js
 * jQuery UI Tabs (Tabs 3)
 * 
 * This is nothing more than the pure jquery UI tabs implementation.
 */
(function($) {

   // if the UI scope is not availalable, add it
  $.viewsUi = $.viewsUi || {};

  $.fn.viewsTabs = function(initial, options) {
    if (initial && initial.constructor == Object) { // shift arguments
      options = initial;
      initial = null;
    }
    options = options || {};

    // first get initial tab from options
    initial = initial && initial.constructor == Number && --initial || 0;

    return this.each(function() {
    new $.viewsUi.tabs(this, $.extend(options, { initial: initial }));
    });
  };

  // chainable tabs methods
  $.each(['Add', 'Remove', 'Enable', 'Disable', 'Click', 'Load'], function(i, method) {
    $.fn['views' + method + 'Tab'] = function() {
      var args = arguments;
      return this.each(function() {
        var instance = $.viewsUi.tabs.instances[this.UI_TABS_UUID];
        instance[method.toLowerCase()].apply(instance, args);
      });
    };
  });
  $.fn.viewsSelectedTab = function(returnElement) {
    var selected;
    if (returnElement) {

    } else {

    }
    return selected;
  };

  $.viewsUi.tabs = function(el, options) {

    this.source = el;

    this.options = $.extend({

      // basic setup
      initial: 0,
      event: 'click',
      disabled: [],
      // TODO bookmarkable: $.ajaxHistory ? true : false,
      unselected: false,
      toggle: options.unselected ? true : false,

      // Ajax
      spinner: 'Loading&#8230;',
      cache: false,
      hashPrefix: 'tab-',

      // animations
      /*fxFade: null,
      fxSlide: null,
      fxShow: null,
      fxHide: null,*/
      fxSpeed: 'normal',
      /*fxShowSpeed: null,
      fxHideSpeed: null,*/

      // callbacks
      add: function() {},
      remove: function() {},
      enable: function() {},
      disable: function() {},
      click: function() {},
      hide: function() {},
      show: function() {},
      load: function() {},

      // CSS classes
      navClass: 'ui-tabs-nav',
      selectedClass: 'ui-tabs-selected',
      disabledClass: 'ui-tabs-disabled',
      containerClass: 'ui-tabs-container',
      hideClass: 'ui-tabs-hide',
      loadingClass: 'ui-tabs-loading'

    }, options);

    this.tabify(true);

    // save instance for later
    var uuid = 'instance-' + $.viewsUi.tabs.prototype.count++;
    $.viewsUi.tabs.instances[uuid] = this;
    this.source['UI_TABS_UUID'] = uuid;

  };

  // static
  $.viewsUi.tabs.instances = {};

  $.extend($.viewsUi.tabs.prototype, {
    animating: false,
    count: 0,
    tabify: function(init) {

      this.$tabs = $('a:first-child', this.source);
      this.$containers = $([]);

      var self = this, o = this.options;

      this.$tabs.each(function(i, a) {
        // inline tab
        if (a.hash && a.hash.replace('#', '')) { // safari 2 reports '#' for an empty hash
          self.$containers = self.$containers.add(a.hash);
        }
        // remote tab
        else {
          var id = a.title && a.title.replace(/\s/g, '_') || o.hashPrefix + (self.count + 1) + '-' + (i + 1), url = a.href;
          a.href = '#' + id;
          a.url = url;
          self.$containers = self.$containers.add(
            $('#' + id)[0] || $('<div id="' + id + '" class="' + o.containerClass + '"></div>')
              .insertAfter( self.$containers[i - 1] || self.source )
          );
        }
      });

      if (init) {

        // Try to retrieve initial tab from fragment identifier in url if present,
        // otherwise try to find selected class attribute on <li>.
        this.$tabs.each(function(i, a) {
          if (location.hash) {
            if (a.hash == location.hash) {
              o.initial = i;
              // prevent page scroll to fragment
              //if (($.browser.msie || $.browser.opera) && !o.remote) {
              if ($.browser.msie || $.browser.opera) {
                var $toShow = $(location.hash), toShowId = $toShow.attr('id');
                $toShow.attr('id', '');
                setTimeout(function() {
                  $toShow.attr('id', toShowId); // restore id
                }, 500);
              }
              scrollTo(0, 0);
              return false; // break
            }
          } else if ( $(a).parents('li:eq(0)').is('li.' + o.selectedClass) ) {
            o.initial = i;
            return false; // break
          }
        });

        // attach necessary classes for styling if not present
        $(this.source).is('.' + o.navClass) || $(this.source).addClass(o.navClass);
        this.$containers.each(function() {
          var $this = $(this);
          $this.is('.' + o.containerClass) || $this.addClass(o.containerClass);
        });

        // highlight tab accordingly
        var $lis = $('li', this.source);
        this.$containers.addClass(o.hideClass);
        $lis.removeClass(o.selectedClass);
        if (!o.unselected) {
          this.$containers.slice(o.initial, o.initial + 1).css('display', 'block');
          $lis.slice(o.initial, o.initial + 1).addClass(o.selectedClass);
        }

        // trigger load of initial tab is remote tab
        if (this.$tabs[o.initial].url) {
          this.load(o.initial + 1, this.$tabs[o.initial].url);
          if (o.cache) {
            this.$tabs[o.initial].url = null; // if loaded once do not load them again
          }
        }

        // disabled tabs
        for (var i = 0, position; position = o.disabled[i]; i++) {
          this.disable(position);
        }

      }

      // setup animations
      var showAnim = {}, hideAnim = {}, showSpeed = o.fxShowSpeed || o.fxSpeed, 
        hideSpeed = o.fxHideSpeed || o.fxSpeed;
      if (o.fxSlide || o.fxFade) {
        if (o.fxSlide) {
          showAnim['height'] = 'show';
          hideAnim['height'] = 'hide';
        }
        if (o.fxFade) {
          showAnim['opacity'] = 'show';
          hideAnim['opacity'] = 'hide';
        }
      } else {
        if (o.fxShow) {
          showAnim = o.fxShow;
        } else { // use some kind of animation to prevent browser scrolling to the tab
          showAnim['min-width'] = 0; // avoid opacity, causes flicker in Firefox
          showSpeed = 1; // as little as 1 is sufficient
        }
        if (o.fxHide) {
          hideAnim = o.fxHide;
        } else { // use some kind of animation to prevent browser scrolling to the tab
          hideAnim['min-width'] = 0; // avoid opacity, causes flicker in Firefox
          hideSpeed = 1; // as little as 1 is sufficient
        }
      }

      // callbacks
      var click = o.click, hide = o.hide, show = o.show;

      // reset some styles to maintain print style sheets etc.
      var resetCSS = { display: '', overflow: '', height: '' };
      if (!$.browser.msie) { // not in IE to prevent ClearType font issue
        resetCSS['opacity'] = '';
      }

      // hide a tab, animation prevents browser scrolling to fragment
      function hideTab(clicked, $hide, $show) {
        $hide.animate(hideAnim, hideSpeed, function() { //
          $hide.addClass(o.hideClass).css(resetCSS); // maintain flexible height and accessibility in print etc.            
          hide(clicked, $show, $hide[0]);
          if ($show) {
            showTab(clicked, $hide, $show);
          }
        });
      }

      // show a tab, animation prevents browser scrolling to fragment
      function showTab(clicked, $hide, $show) {
        // show next tab
        if (!(o.fxSlide || o.fxFade || o.fxShow)) {
          $show.css('display', 'block'); // prevent occasionally occuring flicker in Firefox cause by gap between showing and hiding the tab containers
        }
        $show.animate(showAnim, showSpeed, function() {
          $show.removeClass(o.hideClass).css(resetCSS); // maintain flexible height and accessibility in print etc.
          if ($.browser.msie) {
            $hide[0].style.filter = '';
            $show[0].style.filter = '';
          }
          show(clicked, $show[0], $hide[0]);
          self.animating = false;
        });

      }

      // switch a tab
      function switchTab(clicked, $hide, $show) {
        /*if (o.bookmarkable && trueClick) { // add to history only if true click occured, not a triggered click
          $.ajaxHistory.update(clicked.hash);
        }*/
        $(clicked).parents('li:eq(0)').addClass(o.selectedClass)
          .siblings().removeClass(o.selectedClass);
        hideTab(clicked, $hide, $show);
      }

      // tab click handler
      function tabClick(e) {

        //var trueClick = e.clientX; // add to history only if true click occured, not a triggered click
        var $li = $(this).parents('li:eq(0)'), $hide = self.$containers.filter(':visible'), $show = $(this.hash);

        // if tab may be closed
        if (o.toggle && !$li.is('.' + o.disabledClass) && !self.animating) {       
          if ($li.is('.' + o.selectedClass)) {
            $li.removeClass(o.selectedClass);
            hideTab(this, $hide);
            this.blur();
            return false;
          } else if (!$hide.length) {
            $li.addClass(o.selectedClass);
            showTab(this, $hide, $show);
            this.blur();
            return false;
          }
        }

        // If tab is already selected or disabled, animation is still running or click callback 
        // returns false stop here.
        // Check if click handler returns false last so that it is not executed for a disabled tab!
        if ($li.is('.' + o.selectedClass + ', .' + o.disabledClass) 
          || self.animating || click(this, $show[0], $hide[0]) === false) {
          this.blur();
          return false;
        }

        self.animating = true;

        // show new tab
        if ($show.length) {

          // prevent scrollbar scrolling to 0 and than back in IE7, happens only if bookmarking/history is enabled
          /*if ($.browser.msie && o.bookmarkable) {
            var showId = this.hash.replace('#', '');
            $show.attr('id', '');
            setTimeout(function() {
              $show.attr('id', showId); // restore id
            }, 0);
          }*/

          if (this.url) { // remote tab
            var a = this;
            self.load(self.$tabs.index(this) + 1, this.url, function() {
              switchTab(a, $hide, $show);
            });
            if (o.cache) {
              this.url = null; // if loaded once do not load them again
            }
          } else {
            switchTab(this, $hide, $show);
          }

          // Set scrollbar to saved position - need to use timeout with 0 to prevent browser scroll to target of hash
          /*var scrollX = window.pageXOffset || document.documentElement && document.documentElement.scrollLeft || document.body.scrollLeft || 0;
          var scrollY = window.pageYOffset || document.documentElement && document.documentElement.scrollTop || document.body.scrollTop || 0;
          setTimeout(function() {
            scrollTo(scrollX, scrollY);
          }, 0);*/

        } else {
          throw Drupal.t('jQuery UI Tabs: Mismatching fragment identifier.');
        }

        this.blur(); // prevent IE from keeping other link focussed when using the back button

        //return o.bookmarkable && !!trueClick; // convert trueClick == undefined to Boolean required in IE
        return false;

      }

      // attach click event, avoid duplicates from former tabifying
      this.$tabs.unbind(o.event, tabClick).bind(o.event, tabClick);

    },
    add: function(url, text, position) {
      if (url && text) {
        var o = this.options;
        position = position || this.$tabs.length; // append by default
        if (position >= this.$tabs.length) {
          var method = 'insertAfter';
          position = this.$tabs.length;
        } else {
          var method = 'insertBefore';
        }
        if (url.indexOf('#') == 0) { // ajax container is created by tabify automatically
          var $container = $(url);
          // try to find an existing element before creating a new one
          ($container.length && $container || $('<div id="' + url.replace('#', '') + '" class="' + o.containerClass + ' ' + o.hideClass + '"></div>'))
            [method](this.$containers[position - 1]);
        }
        $('<li><a href="' + url + '"><span>' + text + '</span></a></li>')
          [method](this.$tabs.slice(position - 1, position).parents('li:eq(0)'));
        this.tabify();
        o.add(this.$tabs[position - 1], this.$containers[position - 1]); // callback
      } else {
        throw Drupal.t('jQuery UI Tabs: Not enough arguments to add tab.');
      }
    },
    remove: function(position) {
      if (position && position.constructor == Number) {
        this.$tabs.slice(position - 1, position).parents('li:eq(0)').remove();
        this.$containers.slice(position - 1, position).remove();
        this.tabify();
      }
      this.options.remove(); // callback
    },
    enable: function(position) {
      var $li = this.$tabs.slice(position - 1, position).parents('li:eq(0)'), o = this.options;
      $li.removeClass(o.disabledClass);
      if ($.browser.safari) { // fix disappearing tab after enabling in Safari... TODO check Safari 3
        $li.animate({ opacity: 1 }, 1, function() {
          $li.css({ opacity: '' });
        });
      }
      o.enable(this.$tabs[position - 1], this.$containers[position - 1]); // callback
    },
    disable: function(position) {
      var $li = this.$tabs.slice(position - 1, position).parents('li:eq(0)'), o = this.options;      
      if ($.browser.safari) { // fix opacity of tab after disabling in Safari... TODO check Safari 3
        $li.animate({ opacity: 0 }, 1, function() {
           $li.css({ opacity: '' });
        });
      }
      $li.addClass(this.options.disabledClass);
      o.disable(this.$tabs[position - 1], this.$containers[position - 1]); // callback
    },
    click: function(position) {
      this.$tabs.slice(position - 1, position).trigger('click');
    },
    load: function(position, url, callback) {
      var self = this,
        o = this.options,
        $a = this.$tabs.slice(position - 1, position).addClass(o.loadingClass),
        $span = $('span', $a),
        text = $span.html();

      // shift arguments
      if (url && url.constructor == Function) {
        callback = url;
      }

      // set new URL
      if (url) {
        $a[0].url = url;
      }

      // load
      if (o.spinner) {
        $span.html('<em>' + o.spinner + '</em>');
      }
      setTimeout(function() { // timeout is again required in IE, "wait" for id being restored
        $($a[0].hash).load(url, function() {
          if (o.spinner) {
            $span.html(text);
          }
          $a.removeClass(o.loadingClass);
          // This callback is needed because the switch has to take place after loading
          // has completed.
          if (callback && callback.constructor == Function) {
            callback();
          }
          o.load(self.$tabs[position - 1], self.$containers[position - 1]); // callback
        });
      }, 0);
    }
  });
})(jQuery);
