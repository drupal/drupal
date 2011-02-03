/* vim: set ts=2 sw=2 sts=2 et: */
// GIT $Id$
(function ($) {

Drupal.behaviors.lc3CleanFloatableBox = {
  attach: function (context, settings) {

    // Float button box controller
    $('.floatable-box', context).each(
      function() {
        var elm = $(this);

        // Fix width
        elm.css('width', elm.width() + 'px');

        // Create dump element
        var dump = document.createElement('div');
        dump.className = 'float-box-dump';
        dump = $(dump);
        dump
          .css(
            {
              width: elm.width() + 'px',
              height: elm.height() + 'px'
            }
          )
          .hide();
        elm.after(dump);

        // Calculate limit
        var bottomHeight = elm.height() + 20;
        var topLimit = Math.round(elm.position().top + bottomHeight);

        var scrollHandler = function()
        {
          var showAsFloat = topLimit > ($(document).scrollTop() + $(window).height());

          if ('undefined' == typeof(elm.get(0).showAsFloat) || showAsFloat != elm.get(0).showAsFloat) {

            // State is changed
            elm.get(0).showAsFloat = showAsFloat;

            if (showAsFloat) {

              // Show as float box
              elm
                .css('left', elm.position().left + 'px')
                .addClass('float-box');
              dump.show();

            } else {

              // Show as static box
              elm.removeClass('float-box');
              dump.hide();
            }
          }
        }

        $(document).scroll(scrollHandler);
        scrollHandler();
      }
    );

  }
}

})(jQuery);

