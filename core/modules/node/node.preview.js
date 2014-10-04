(function ($, Drupal) {

  "use strict";

  /**
   * Disabling all links (except local fragment identifiers such as href="#frag")
   * in node previews to prevent users from leaving the page.
   */
  Drupal.behaviors.nodePreviewDestroyLinks = {
    attach: function (context) {

      function clickPreviewModal(event) {
        // Only confirm leaving previews when left-clicking and user is not
        // pressing the ALT, CTRL, META (Command key on the Macintosh keyboard)
        // or SHIFT key.
        if (event.button === 0 && !event.altKey && !event.ctrlKey && !event.metaKey && !event.shiftKey) {
          event.preventDefault();
          var $previewDialog = $('<div>' + Drupal.theme('nodePreviewModal') + '</div>').appendTo('body');
          Drupal.dialog($previewDialog, {
            title: Drupal.t('Leave preview?'),
            buttons: [
              {
                text: Drupal.t('Cancel'),
                click: function () {
                  $(this).dialog('close');
                }
              }, {
                text: Drupal.t('Leave preview'),
                click: function () {
                  window.top.location.href = event.target.href;
                }
              }
            ]
          }).showModal();
        }
      }

      var $preview = $(context).find('.content').once('node-preview');
      if ($(context).find('.node-preview-container').length) {
        $preview.on('click.preview', 'a:not([href^=#], #edit-backlink, #toolbar-administration a)', clickPreviewModal);
      }
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        var $preview = $(context).find('.content').removeOnce('node-preview');
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
        $autosubmit.on('formUpdated.preview', function () {
          $(this.form).trigger('submit');
        });
      }
    }
  };

  Drupal.theme.nodePreviewModal = function () {
    return '<p>' +
      Drupal.t('Leaving the preview will cause unsaved changes to be lost. Are you sure you want to leave the preview?') +
      '</p><small class="description">' +
      Drupal.t('CTRL+Left click will prevent this dialog from showing and proceed to the clicked link.') + '</small>';
  };

})(jQuery, Drupal);
