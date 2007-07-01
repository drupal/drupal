// $Id: tableheader.js,v 1.4 2007/07/01 15:37:08 dries Exp $

Drupal.behaviors.tableHeader = function (context) {
  // Keep track of all header cells.
  var cells = [];

  var z = 0;
  $('table thead:not(.tableHeader-processed)', context).each(function () {
    // Find table height.
    var table = $(this).parent('table')[0];
    var height = $(table).addClass('sticky-table').height();
    var i = 0;

    // Find all header cells.
    $('th', this).each(function () {

      // Ensure each cell has an element in it.
      var html = $(this).html();
      if (html == ' ') {
        html = '&nbsp;';
      }
      if ($(this).children().size() == 0) {
        html = '<span>'+ html +'</span>';
      }

      // Clone and wrap cell contents in sticky wrapper that overlaps the cell's padding.
      $('<div class="sticky-header" style="position: fixed; visibility: hidden; top: 0px;">'+ html +'</div>').prependTo(this);
      var div = $('div.sticky-header', this).css({
        'marginLeft': '-'+ $(this).css('paddingLeft'),
        'marginRight': '-'+ $(this).css('paddingRight'),
        'paddingLeft': $(this).css('paddingLeft'),
        'paddingTop': $(this).css('paddingTop'),
        'paddingBottom': $(this).css('paddingBottom'),
        'z-index': ++z
      })[0];
      cells.push(div);

      // Adjust width to fit cell/table.
      var ref = this;
      if (!i++) {
        // The first cell is as wide as the table to prevent gaps.
        ref = table;
        div.wide = true;
      }
      $(div).css('width', parseInt($(ref).width())
                        - parseInt($(div).css('paddingLeft')) +'px');

      // Get position and store.
      div.cell = this;
      div.table = table;
      div.stickyMax = height;
      div.stickyPosition = Drupal.absolutePosition(this).y;
    });
    $(this).addClass('tableHeader-processed');
  });

  // Track scrolling.
  var scroll = function() {
    $(cells).each(function () {
      // Fetch scrolling position.
      var scroll = document.documentElement.scrollTop || document.body.scrollTop;
      var offset = scroll - this.stickyPosition - 4;
      if (offset > 0 && offset < this.stickyMax - 100) {
        $(this).css('visibility', 'visible');
      }
      else {
        $(this).css('visibility', 'hidden');
      }
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

      // Precalculate table heights
      $('table.sticky-table').each(function () {
        this.height = $(this).height();
      });

      $(cells).each(function () {
        // Get position.
        this.stickyPosition = Drupal.absolutePosition(this.cell).y;
        this.stickyMax = this.table.height;

        // Reflow the cell.
        var ref = this.cell;
        if (this.wide) {
          // Resize the first cell to fit the table.
          ref = this.table;
        }
        $(this).css('width', parseInt($(ref).width())
                           - parseInt($(this).css('paddingLeft')) +'px');
      });

      // Reset timer
      time = null;
    }, 250);
  };
  $(window).resize(resize);
};
