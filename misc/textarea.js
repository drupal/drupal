// $Id$
(function ($) {

Drupal.behaviors.textarea = {
  attach: function (context, settings) {
    $('textarea.resizable', context).once('textarea', function () {
      // When wrapping the text area, work around an IE margin bug. See:
      // http://jaspan.com/ie-inherited-margin-bug-form-elements-and-haslayout
      var staticOffset = null;
      var textarea = $(this).wrap('<div class="resizable-textarea"><span></span></div>');
      var grippie = $('<div class="grippie"></div>').mousedown(startDrag);

      grippie
        .insertAfter(textarea)
        .css('margin-right', grippie.width() - textarea.width());

      function startDrag(e) {
        staticOffset = textarea.height() - e.pageY;
        textarea.css('opacity', 0.25);
        $(document).mousemove(performDrag).mouseup(endDrag);
        return false;
      }

      function performDrag(e) {
        textarea.height(Math.max(32, staticOffset + e.pageY) + 'px');
        return false;
      }

      function endDrag(e) {
        $(document).unbind('mousemove', performDrag).unbind('mouseup', endDrag);
        textarea.css('opacity', 1);
      }
    });
  }
};

})(jQuery);
