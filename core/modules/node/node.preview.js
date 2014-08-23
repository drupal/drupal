(function ($, Drupal) {

  "use strict";

  /**
   * Disabling all links (except local fragment identifiers such as href="#frag")
   * in node previews to prevent users from leaving the page.
   */
  Drupal.behaviors.nodePreviewDestroyLinks = {
    attach: function (context) {
      var $preview = $(context).find('.page-node-preview').once('node-preview');
      if ($preview.length) {
        $preview.on('click.preview', 'a:not([href^=#], #edit-backlink, #toolbar-administration a)', function (e) {
          e.preventDefault();
        });
      }
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        var $preview = $(context).find('.page-node-preview').removeOnce('node-preview');
        if ($preview.length) {
          $preview.off('click.preview');
        }
      }
    }
  };

  /**
   * Switch view mode.
   */
  Drupal.behaviors.nodePreviewSwitchViewMode = {
    attach: function (context) {
      var $autosubmit = $(context).find('[data-drupal-autosubmit]').once('autosubmit');
      if ($autosubmit.length) {
        $autosubmit.on('formUpdated.preview', function() {
          $(this.form).trigger('submit');
        });
      }
    }
  };

})(jQuery, Drupal);
