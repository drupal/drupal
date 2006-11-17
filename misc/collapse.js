// $Id: collapse.js,v 1.8 2006/11/17 20:36:46 drumm Exp $

Drupal.collapseAutoAttach = function () {
  $('fieldset.collapsible legend').each(function () {
    // Turn the legend into clickable link
    var a = document.createElement('a');
    a.href = '#';
    $(a)
      .click(function() {
        var fieldset = this.parentNode.parentNode;

        // Prevent double animations
        if (fieldset.animating) {
          return false;
        }
        fieldset.animating = true;

        if ($(fieldset).is('.collapsed')) {
          // Open fieldset with animation
          $(fieldset.contentWrapper).hide();
          $(fieldset).removeClass('collapsed');
          $(fieldset.contentWrapper).slideDown(300,
            {
              // Make sure we open to height auto
              complete: function() {
                $(fieldset.contentWrapper).css('height', 'auto');
                Drupal.collapseScrollIntoView(fieldset);
                fieldset.animating = false;
              },
              // Scroll the fieldset into view
              step: function() {
                Drupal.collapseScrollIntoView(fieldset);
              }
            }
          );
          if (typeof Drupal.textareaAttach != 'undefined') {
            // Initialize resizable textareas that are now revealed
            Drupal.textareaAttach(null, fieldset);
          }
        }
        else {
          // Collapse fieldset with animation (reverse of opening)
          $(fieldset.contentWrapper)
            .slideUp('medium', function () { $(fieldset).addClass('collapsed'); fieldset.animating = false; } )
            .show();
        }
        return false;
      })
      .html(this.innerHTML);
    $(this)
      .empty()
      .append(a);

    // Wrap fieldsets contents (except for the legend) into wrapper divs for animating.
    // div1 is used to avoid margin problems inside fieldsets,
    // div2 is the one that is actually animated.
    var div1 = document.createElement('div');
    var div2 = document.createElement('div');
    this.parentNode.contentWrapper = div2;
    $(this).after(div1);
    $(div1).append(div2);
    var el = div1.nextSibling;
    while (el != null) {
      var next = el.nextSibling;
      $(el).remove();
      $(div2).append(el);
      el = next;
    }
    // Avoid jumping around due to margins collapsing into fieldset border
    $(div1).css('overflow', 'hidden');

    // Expand if there are errors inside
    if ($('input.error, textarea.error, select.error', this.parentNode).size() > 0) {
      $(this.parentNode).removeClass('collapsed');
    }
  });
}

/**
 * Scroll a given fieldset into view as much as possible.
 */
Drupal.collapseScrollIntoView = function (node) {
  var h = self.innerHeight || document.documentElement.clientHeight || document.body.clientHeight || 0;
  var offset = self.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
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
  $(document).ready(Drupal.collapseAutoAttach);
}
