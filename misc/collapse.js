// $Id: collapse.js,v 1.10 2007/01/11 03:38:31 unconed Exp $

/**
 * Toggle the visibility of a fieldset using smooth animations
 */
Drupal.toggleFieldset = function(fieldset) {
  if ($(fieldset).is('.collapsed')) {
    var content = $('> div', fieldset).hide();
    $(fieldset).removeClass('collapsed');
    content.slideDown(300, {
      complete: function() {
        // Make sure we open to height auto
        $(this).css('height', 'auto');
        Drupal.collapseScrollIntoView(this.parentNode);
        this.parentNode.animating = false;
      },
      step: function() {
         // Scroll the fieldset into view
        Drupal.collapseScrollIntoView(this.parentNode);
      }
    });
    if (typeof Drupal.textareaAttach != 'undefined') {
      // Initialize resizable textareas that are now revealed
      Drupal.textareaAttach(null, fieldset);
    }
  }
  else {
    var content = $('> div', fieldset).slideUp('medium', function() {
      $(this.parentNode).addClass('collapsed');
      this.parentNode.animating = false;
    });
  }
}

/**
 * Scroll a given fieldset into view as much as possible.
 */
Drupal.collapseScrollIntoView = function (node) {
  var h = self.innerHeight || document.documentElement.clientHeight || $('body')[0].clientHeight || 0;
  var offset = self.pageYOffset || document.documentElement.scrollTop || $('body')[0].scrollTop || 0;
  var pos = Drupal.absolutePosition(node);
  var fudge = 55;
  if (pos.y + node.offsetHeight + fudge > h + offset) {
    if (node.offsetHeight > h) {
      window.scrollTo(0, pos.y);
    } else {
      window.scrollTo(0, pos.y + node.offsetHeight - h + fudge);
    }
  }
}

// Global Killswitch
if (Drupal.jsEnabled) {
  $(document).ready(function() {
    $('fieldset.collapsible > legend').each(function() {
      var fieldset = $(this.parentNode);
      // Expand if there are errors inside
      if ($('input.error, textarea.error, select.error', fieldset).size() > 0) {
        fieldset.removeClass('collapsed');
      }

      // Turn the legend into a clickable link and wrap the contents of the fieldset
      // in a div for easier animation
      var text = this.innerHTML;
      $(this).empty().append($('<a href="#">'+ text +'</a>').click(function() {
        var fieldset = $(this).parents('fieldset:first')[0];
        // Don't animate multiple times
        if (!fieldset.animating) {
          fieldset.animating = true;
          Drupal.toggleFieldset(fieldset);
        }
        return false;
      })).after($('<div class="fieldset-wrapper"></div>').append(fieldset.children(':not(legend)')));
    });
  });
}
