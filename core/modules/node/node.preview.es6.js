/**
 * @file
 * Preview behaviors.
 */

(function ($, Drupal) {
  /**
   * Disables all non-relevant links in node previews.
   *
   * Destroys links (except local fragment identifiers such as href="#frag") in
   * node previews to prevent users from leaving the page.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches confirmation prompt for clicking links in node preview mode.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches confirmation prompt for clicking links in node preview mode.
   */
  Drupal.behaviors.nodePreviewDestroyLinks = {
    attach(context) {
      function clickPreviewModal(event) {
        // Only confirm leaving previews when left-clicking and user is not
        // pressing the ALT, CTRL, META (Command key on the Macintosh keyboard)
        // or SHIFT key.
        if (
          event.button === 0 &&
          !event.altKey &&
          !event.ctrlKey &&
          !event.metaKey &&
          !event.shiftKey
        ) {
          event.preventDefault();
          const $previewDialog = $(
            `<div>${Drupal.theme('nodePreviewModal')}</div>`,
          ).appendTo('body');
          Drupal.dialog($previewDialog, {
            title: Drupal.t('Leave preview?'),
            buttons: [
              {
                text: Drupal.t('Cancel'),
                click() {
                  $(this).dialog('close');
                },
              },
              {
                text: Drupal.t('Leave preview'),
                click() {
                  window.top.location.href = event.target.href;
                },
              },
            ],
          }).showModal();
        }
      }

      if (!context.querySelector('.node-preview-container')) {
        return;
      }
      if (once('node-preview', 'html').length) {
        $(document).on(
          'click.preview',
          'a:not([href^="#"], .node-preview-container a)',
          clickPreviewModal,
        );
      }
    },
    detach(context, settings, trigger) {
      if (trigger === 'unload') {
        if (
          context.querySelector('.node-preview-container') &&
          once.remove('node-preview', 'html').length
        ) {
          $(document).off('click.preview');
        }
      }
    },
  };

  /**
   * Switch view mode.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches automatic submit on `formUpdated.preview` events.
   */
  Drupal.behaviors.nodePreviewSwitchViewMode = {
    attach(context) {
      const autosubmit = once(
        'autosubmit',
        '[data-drupal-autosubmit]',
        context,
      );
      if (autosubmit.length) {
        $(autosubmit).on('formUpdated.preview', function () {
          $(this.form).trigger('submit');
        });
      }
    },
  };

  /**
   * Theme function for node preview modal.
   *
   * @return {string}
   *   Markup for the node preview modal.
   */
  Drupal.theme.nodePreviewModal = function () {
    return `<p>${Drupal.t(
      'Leaving the preview will cause unsaved changes to be lost. Are you sure you want to leave the preview?',
    )}</p><small class="description">${Drupal.t(
      'CTRL+Left click will prevent this dialog from showing and proceed to the clicked link.',
    )}</small>`;
  };
})(jQuery, Drupal);
