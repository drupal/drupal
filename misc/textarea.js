// $Id: textarea.js,v 1.14 2007/04/10 11:24:16 dries Exp $

Drupal.textareaAttach = function() {
  $('textarea.resizable:not(.processed)').each(function() {
    var textarea = $(this).addClass('processed'), staticOffset = null;

    // When wrapping the text area, work around an IE margin bug.  See:
    // http://jaspan.com/ie-inherited-margin-bug-form-elements-and-haslayout
    $(this).wrap('<div class="resizable-textarea"><span></span></div>')
      .parent().append($('<div class="grippie"></div>').mousedown(startDrag));

    // Inherit visibility
    if ($(this).is(':hidden')) {
      $(this).parent().hide();
      $(this).show();
    }

    var grippie = $('div.grippie', $(this).parent())[0];
    grippie.style.marginRight = (grippie.offsetWidth - $(this)[0].offsetWidth) +'px';

    function startDrag(e) {
      staticOffset = textarea.height() - Drupal.mousePosition(e).y;
      textarea.css('opacity', 0.25);
      $(document).mousemove(performDrag).mouseup(endDrag);
      return false;
    }

    function performDrag(e) {
      textarea.height(Math.max(32, staticOffset + Drupal.mousePosition(e).y) + 'px');
      return false;
    }

    function endDrag(e) {
      $(document).unbind("mousemove");
      $(document).unbind("mouseup");
      textarea.css('opacity', 1);
    }
  });
}

if (Drupal.jsEnabled) {
  $(document).ready(Drupal.textareaAttach);
}
