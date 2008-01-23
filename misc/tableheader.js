// $Id: tableheader.js,v 1.15 2008/01/23 09:48:10 goba Exp $

Drupal.behaviors.tableHeader = function (context) {
  // This breaks in anything less than IE 7. Prevent it from running.
  if (jQuery.browser.msie && parseInt(jQuery.browser.version) < 7) {
    return;
  }

  // Keep track of all cloned table headers.
  var headers = [];

  $('table.sticky-enabled thead:not(.tableHeader-processed)', context).each(function () {
    // Clone thead so it inherits original jQuery properties.
    var headerClone = $(this).clone(true).insertBefore(this.parentNode).wrap('<table class="sticky-header"></table>').parent().css({
      position: 'fixed',
      top: '0px'
    });

    var headerClone = $(headerClone)[0];
    headers.push(headerClone);

    // Store parent table.
    var table = $(this).parent('table')[0];
    headerClone.table = table;
    // Finish initialzing header positioning.
    tracker(headerClone);

    $(table).addClass('sticky-table');
    $(this).addClass('tableHeader-processed');
  });

  // Track positioning and visibility.
  function tracker(e) {
    // Save positioning data.
    var viewHeight = document.documentElement.scrollHeight || document.body.scrollHeight;
    if (e.viewHeight != viewHeight) {
      e.viewHeight = viewHeight;
      e.vPosition = $(e.table).offset().top - 4;
      e.hPosition = $(e.table).offset().left;
      e.vLength = e.table.clientHeight - 100;
      // Resize header and its cell widths.
      var parentCell = $('th', e.table);
      $('th', e).each(function(index) {
        var cellWidth = parentCell.eq(index).css('width');
        // Exception for IE7.
        if (cellWidth == 'auto') {
          var cellWidth = parentCell.get(index).clientWidth +'px';
        }
        $(this).css('width', cellWidth);
      });
      $(e).css('width', $(e.table).css('width'));
    }

    // Track horizontal positioning relative to the viewport and set visibility.
    var hScroll = document.documentElement.scrollLeft || document.body.scrollLeft;
    var vOffset = (document.documentElement.scrollTop || document.body.scrollTop) - e.vPosition;
    var visState = (vOffset > 0 && vOffset < e.vLength) ? 'visible' : 'hidden';
    $(e).css({left: -hScroll + e.hPosition +'px', visibility: visState});
  };

  // Track scrolling.
  var scroll = function() {
    $(headers).each(function () {
      tracker(this);
    });
  };
  $(window).scroll(scroll);
  $(document.documentElement).scroll(scroll);

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
      // Reset timer
      time = null;
    }, 250);
  };
  $(window).resize(resize);
};
