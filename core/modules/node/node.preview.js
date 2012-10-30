(function ($, Drupal) {

"use strict";

/**
 * Disabling all links in node previews to prevent users from leaving the page.
 */
Drupal.behaviors.nodePreviewDestroyLinks = {
  attach: function (context) {
    var $preview = $(context).find('.node.preview').once('node-preview');
    if ($preview.length) {
      $preview.on('click.preview', 'a', function (e) {
        e.preventDefault();
      });
    }
  },
  detach: function (context, settings, trigger) {
    if (trigger === 'unload') {
      var $preview = $(context).find('.node.preview').removeOnce('node-preview');
      if ($preview.length) {
        $preview.off('click.preview');
      }
    }
  }
};

})(jQuery, Drupal);
