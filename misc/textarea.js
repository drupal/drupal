// $Id: textarea.js,v 1.18 2007/09/12 18:29:32 goba Exp $

Drupal.behaviors.textarea = function(context) {
  $('textarea.resizable:not(.textarea-processed)', context).each(function() {
    var textarea = $(this).addClass('textarea-processed'), staticOffset = null;

    // When wrapping the text area, work around an IE margin bug.  See:
    // http://jaspan.com/ie-inherited-margin-bug-form-elements-and-haslayout
    $(this).wrap('<div class="resizable-textarea"><span></span></div>')
      .parent().append($('<div class="grippie"></div>').mousedown(startDrag));

    // Inherit visibility
    if ($(this).is('[@disabled]')) {
      $(this).parent().hide();
      $(this).show();
    }

    var grippie = $('div.grippie', $(this).parent())[0];
    grippie.style.marginRight = (grippie.offsetWidth - $(this)[0].offsetWidth) +'px';

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
      $(document).unbind("mousemove");
      $(document).unbind("mouseup");
      textarea.css('opacity', 1);
    }
  });
};
