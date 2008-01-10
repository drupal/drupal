// $Id: tableheader.js,v 1.11 2008/01/10 17:59:38 goba Exp $

Drupal.behaviors.tableHeader = function (context) {
  // This breaks in anything less than IE 7. Prevent it from running.
  if (jQuery.browser.msie && parseInt(jQuery.browser.version) < 7) {
    return;
  }

  // Keep track of all cloned table headers.
  var headers = [];

  $('table thead:not(.tableHeader-processed)', context).each(function () {
    // Clone table and remove unwanted elements so it inherits original properties.
    var headerClone = $(this.parentNode).clone(true).insertBefore(this.parentNode).addClass('sticky-header').css({
      position: 'fixed',
      visibility: 'hidden',
      top: '0px'
    });

    // Sets an id for cloned table header.
    var headerID = headerClone.attr('id');
    if (headerID != '') {
      headerClone.attr('id', headerID + '-header');
    }

    // Everything except thead must be removed. See theme_table().
    $('tbody', headerClone).remove();
    $('caption', headerClone).remove();

    var headerClone = $(headerClone)[0];
    headers.push(headerClone);

    // Store parent table.
    var table = $(this).parent('table')[0];
    headerClone.table = table;
    // Finish initialzing header positioning.
    headerClone.resizeWidths = true;
    tracker(headerClone);

    $(table).addClass('sticky-table');
    $(this).addClass('tableHeader-processed');
  });

  // Track positioning and visibility.
  function tracker(e) {
    // Save positioning data.
    var viewHeight = document.documentElement.scrollHeight || document.body.scrollHeight;
    if (e.viewHeight != viewHeight || e.resizeWidths) {
      e.viewHeight = viewHeight;
      e.vPosition = $(e.table).offset().top;
      e.hPosition = $(e.table).offset().left;
      e.vLength = $(e.table).height();
    }

    // Track horizontal positioning relative to the viewport and set visibility.
    var hScroll = document.documentElement.scrollLeft || document.body.scrollLeft;
    var vScroll = document.documentElement.scrollTop || document.body.scrollTop;
    var vOffset = vScroll - e.vPosition - 4;
    var visState = (vOffset > 0 && vOffset < e.vLength - 100) ? 'visible' : 'hidden';
    $(e).css({left: -hScroll + e.hPosition +'px', visibility: visState});

    // Resize cell widths.
    if (e.resizeWidths) {
      var cellCount = 0;
      $('th', e).each(function() {
        var cellWidth = parseInt($('th', e.table).eq(cellCount).css('width'));
        // Exception for IE7.
        if (!cellWidth) {
          var cellWidth = $('th', e.table).eq(cellCount).width();
        }
        cellCount++;
        $(this).css('width', cellWidth +'px');
      });
      $(e).css('width', $(e.table).width() +'px');
      e.resizeWidths = false;
    }
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
      $(headers).each(function () {
        this.resizeWidths = true;
        tracker(this);
      });
      // Reset timer
      time = null;
    }, 250);
  };
  $(window).resize(resize);
};
