// $Id: textarea.js,v 1.20 2007/11/30 23:26:58 goba Exp $

Drupal.behaviors.textarea = function(context) {
  $('textarea.resizable:not(.textarea-processed)', context).each(function() {
    var textarea = $(this).addClass('textarea-processed'), staticOffset = null;

    // Make sure that teaser.js has done its magic before converting this textarea.
    if (Drupal.behaviors.teaser && textarea.is(('.teaser:not(.teaser-processed)'))) {
      Drupal.behaviors.teaser(this.parentNode);
    }

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
      $(document).unbind("mousemove", performDrag).unbind("mouseup", endDrag);
      textarea.css('opacity', 1);
    }
  });
};
