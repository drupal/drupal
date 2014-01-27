(function ($, Drupal) {

  "use strict";

  /**
   * Disabling all links (except local fragment identifiers such as href="#frag")
   * in node previews to prevent users from leaving the page.
   */
  Drupal.behaviors.nodePreviewDestroyLinks = {
    attach: function (context) {
      var $preview = $(context).find('.node').once('node-preview');
      if ($preview.length) {
        $preview.on('click.preview', 'a:not([href^=#])', function (e) {
          e.preventDefault();
        });
      }
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        var $preview = $(context).find('.node').removeOnce('node-preview');
        if ($preview.length) {
          $preview.off('click.preview');
        }
      }
    }
  };

})(jQuery, Drupal);
