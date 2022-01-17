/**
 * @file
 * Menu UI behaviors.
 */

(function ($, Drupal) {
  /**
   * Set a summary on the menu link form.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Find the form and call `drupalSetSummary` on it.
   */
  Drupal.behaviors.menuUiDetailsSummaries = {
    attach(context) {
      $(context)
        .find('.menu-link-form')
        .drupalSetSummary((context) => {
          const $context = $(context);
          if (
            $context.find('.js-form-item-menu-enabled input').is(':checked')
          ) {
            return Drupal.checkPlain(
              $context.find('.js-form-item-menu-title input')[0].value,
            );
          }

          return Drupal.t('Not in menu');
        });
    },
  };

  /**
   * Automatically fill in a menu link title, if possible.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches change and keyup behavior for automatically filling out menu
   *   link titles.
   */
  Drupal.behaviors.menuUiLinkAutomaticTitle = {
    attach(context) {
      const $context = $(context);
      $context.find('.menu-link-form').each(function () {
        const $this = $(this);
        // Try to find menu settings widget elements as well as a 'title' field
        // in the form, but play nicely with user permissions and form
        // alterations.
        const $checkbox = $this.find('.js-form-item-menu-enabled input');
        const $linkTitle = $context.find('.js-form-item-menu-title input');
        const $title = $this
          .closest('form')
          .find('.js-form-item-title-0-value input');
        // Bail out if we do not have all required fields.
        if (!($checkbox.length && $linkTitle.length && $title.length)) {
          return;
        }
        // If there is a link title already, mark it as overridden. The user
        // expects that toggling the checkbox twice will take over the node's
        // title.
        if ($checkbox.is(':checked') && $linkTitle[0].value.length) {
          $linkTitle.data('menuLinkAutomaticTitleOverridden', true);
        }
        // Whenever the value is changed manually, disable this behavior.
        $linkTitle.on('keyup', () => {
          $linkTitle.data('menuLinkAutomaticTitleOverridden', true);
        });
        // Global trigger on checkbox (do not fill-in a value when disabled).
        $checkbox.on('change', () => {
          if ($checkbox.is(':checked')) {
            if (!$linkTitle.data('menuLinkAutomaticTitleOverridden')) {
              $linkTitle[0].value = $title[0].value;
            }
          } else {
            $linkTitle[0].value = '';
            $linkTitle.removeData('menuLinkAutomaticTitleOverridden');
          }
          $checkbox.closest('.vertical-tabs-pane').trigger('summaryUpdated');
          $checkbox.trigger('formUpdated');
        });
        // Take over any title change.
        $title.on('keyup', () => {
          if (
            !$linkTitle.data('menuLinkAutomaticTitleOverridden') &&
            $checkbox.is(':checked')
          ) {
            $linkTitle[0].value = $title[0].value;
            $linkTitle.trigger('formUpdated');
          }
        });
      });
    },
  };
})(jQuery, Drupal);
