// $Id: textarea.js,v 1.13 2007/04/09 13:58:02 dries Exp $

Drupal.textareaAttach = function() {
  $('textarea.resizable:not(.processed)').each(function() {
    var textarea = $(this).addClass('processed'), staticOffset = null;

    $(this).wrap('<div class="resizable-textarea"></div>')
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
