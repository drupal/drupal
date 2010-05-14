// $Id: tableheader.js,v 1.30 2010/05/14 07:45:53 dries Exp $
(function ($) {

Drupal.tableHeaderDoScroll = function () {
  if ($.isFunction(Drupal.tableHeaderOnScroll)) {
    Drupal.tableHeaderOnScroll();
  }
};

Drupal.behaviors.tableHeader = {
  attach: function (context, settings) {
    // This breaks in anything less than IE 7. Prevent it from running.
    if ($.browser.msie && parseInt($.browser.version, 10) < 7) {
      return;
    }

    $('table.sticky-enabled thead', context).once('tableheader', function () {
      // Clone the table header so it inherits original jQuery properties. Hide
      // the table to avoid a flash of the header clone upon page load.
      var headerClone = $(this).clone(true).hide().insertBefore(this.parentNode).wrap('<table class="sticky-header"></table>').parent().css({
        position: 'fixed',
        top: '0px'
      });

      headerClone = $(headerClone)[0];

      // Store parent table.
      var table = $(this).parent('table')[0];
      headerClone.table = table;
      // Finish initializing header positioning.
      tracker(headerClone);
      // We hid the header to avoid it showing up erroneously on page load;
      // we need to unhide it now so that it will show up when expected.
      $(headerClone).children('thead').show();

      $(table).addClass('sticky-table');
    });

    // Define the anchor holding var.
    var prevAnchor = '';

    // Track positioning and visibility.
    function tracker(e) {
      // Reset top position of sticky table headers to the current top offset.
      var topOffset = Drupal.displace ? Drupal.displace.getDisplacement('top') : 0;
      $('.sticky-header').css('top', topOffset + 'px');
      // Save positioning data.
      var viewHeight = document.documentElement.scrollHeight || document.body.scrollHeight;
      if (e.viewHeight != viewHeight) {
        e.viewHeight = viewHeight;
        e.vPosition = $(e.table).offset().top - 4 - topOffset;
        e.hPosition = $(e.table).offset().left;
        e.vLength = e.table.clientHeight - 100;
        // Resize header and its cell widths.
        var parentCell = $('th', e.table);
        $('th', e).each(function (index) {
          var cellWidth = parentCell.eq(index).css('width');
          // Exception for IE7.
          if (cellWidth == 'auto') {
            cellWidth = parentCell.get(index).clientWidth + 'px';
          }
          $(this).css('width', cellWidth);
        });
        $(e).css('width', $(e.table).css('width'));
      }

      // Track horizontal positioning relative to the viewport and set visibility.
      var hScroll = document.documentElement.scrollLeft || document.body.scrollLeft;
      var vOffset = (document.documentElement.scrollTop || document.body.scrollTop) - e.vPosition;
      var visState = (vOffset > 0 && vOffset < e.vLength) ? 'visible' : 'hidden';
      $(e).css({ left: -hScroll + e.hPosition + 'px', visibility: visState });

      // Check the previous anchor to see if we need to scroll to make room for the header.
      // Get the height of the header table and scroll up that amount.
      if (prevAnchor != location.hash) {
        if (location.hash != '') {
          var scrollLocation = $('td' + location.hash).offset().top - $(e).height();
          $('body, html').scrollTop(scrollLocation);
        }
        prevAnchor = location.hash;
      }
    }

    // Only attach to scrollbars once, even if Drupal.attachBehaviors is called
    //  multiple times.
    $('body').once(function () {
      $(window).scroll(Drupal.tableHeaderDoScroll);
      $(document.documentElement).scroll(Drupal.tableHeaderDoScroll);
    });

    // Track scrolling.
    Drupal.tableHeaderOnScroll = function () {
      $('table.sticky-header').each(function () {
        tracker(this);
      });
    };

    // Track resizing.
    var time = null;
    var resize = function () {
      // Ensure minimum time between adjustments.
      if (time) {
        return;
      }
      time = setTimeout(function () {
        $('table.sticky-header').each(function () {
          // Force cell width calculation.
          this.viewHeight = 0;
          tracker(this);
        });
        // Reset timer.
        time = null;
      }, 250);
    };
    $(window).resize(resize);
  }
};

})(jQuery);
