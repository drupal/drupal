/**
 * @file
 * Handles AJAX submission and response in Views UI.
 */

(function ($, Drupal, drupalSettings) {
  /**
   * Ajax command for highlighting elements.
   *
   * @param {Drupal.Ajax} [ajax]
   *   An Ajax object.
   * @param {object} response
   *   The Ajax response.
   * @param {string} response.selector
   *   The selector in question.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.viewsHighlight = function (
    ajax,
    response,
    status,
  ) {
    $('.hilited').removeClass('hilited');
    $(response.selector).addClass('hilited');
  };

  /**
   * Ajax command to set the form submit action in the views modal edit form.
   *
   * @param {Drupal.Ajax} [ajax]
   *   An Ajax object.
   * @param {object} response
   *   The Ajax response. Contains .url
   * @param {string} [status]
   *   The XHR status code?
   */
  Drupal.AjaxCommands.prototype.viewsSetForm = function (
    ajax,
    response,
    status,
  ) {
    const $form = $('.js-views-ui-dialog form');
    // Identify the button that was clicked so that .ajaxSubmit() can use it.
    // We need to do this for both .click() and .mousedown() since JavaScript
    // code might trigger either behavior.
    const $submitButtons = $(
      once(
        'views-ajax-submit',
        $form.find('input[type=submit].js-form-submit, button.js-form-submit'),
      ),
    );
    $submitButtons.on('click mousedown', function () {
      this.form.clk = this;
    });
    once('views-ajax-submit', $form).forEach((form) => {
      const $form = $(form);
      const elementSettings = {
        url: response.url,
        event: 'submit',
        base: $form.attr('id'),
        element: form,
      };
      const ajaxForm = Drupal.ajax(elementSettings);
      ajaxForm.$form = $form;
    });
  };

  /**
   * Ajax command to show certain buttons in the views edit form.
   *
   * @param {Drupal.Ajax} [ajax]
   *   An Ajax object.
   * @param {object} response
   *   The Ajax response.
   * @param {boolean} response.changed
   *   Whether the state changed for the buttons or not.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.viewsShowButtons = function (
    ajax,
    response,
    status,
  ) {
    $('div.views-edit-view div.form-actions').removeClass('js-hide');
    if (response.changed) {
      $('div.views-edit-view div.view-changed.messages').removeClass('js-hide');
    }
  };

  /**
   * Ajax command for triggering preview.
   *
   * @param {Drupal.Ajax} [ajax]
   *   An Ajax object.
   * @param {object} [response]
   *   The Ajax response.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.viewsTriggerPreview = function (
    ajax,
    response,
    status,
  ) {
    if ($('input#edit-displays-live-preview')[0].checked) {
      $('#preview-submit').trigger('click');
    }
  };

  /**
   * Ajax command to replace the title of a page.
   *
   * @param {Drupal.Ajax} [ajax]
   *   An Ajax object.
   * @param {object} response
   *   The Ajax response.
   * @param {string} response.siteName
   *   The site name.
   * @param {string} response.title
   *   The new page title.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.viewsReplaceTitle = function (
    ajax,
    response,
    status,
  ) {
    const doc = document;
    // For the <title> element, make a best-effort attempt to replace the page
    // title and leave the site name alone. If the theme doesn't use the site
    // name in the <title> element, this will fail.
    const oldTitle = doc.title;
    // Escape the site name, in case it has special characters in it, so we can
    // use it in our regex.
    const escapedSiteName = response.siteName.replace(
      /[-[\]{}()*+?.,\\^$|#\s]/g,
      '\\$&',
    );
    const re = new RegExp(`.+ (.) ${escapedSiteName}`);
    doc.title = oldTitle.replace(
      re,
      `${response.title} $1 ${response.siteName}`,
    );
    document.querySelectorAll('h1.page-title').forEach((item) => {
      item.textContent = response.title;
    });
  };

  /**
   * Get rid of irritating tabledrag messages.
   *
   * @return {Array}
   *   An array of messages. Always empty array, to get rid of the messages.
   */
  Drupal.theme.tableDragChangedWarning = function () {
    return [];
  };

  /**
   * Trigger preview when the "live preview" checkbox is checked.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to trigger live preview if the live preview option is
   *   checked.
   */
  Drupal.behaviors.livePreview = {
    attach(context) {
      $(once('views-ajax', 'input#edit-displays-live-preview', context)).on(
        'click',
        function () {
          if (this.checked) {
            $('#preview-submit').trigger('click');
          }
        },
      );
    },
  };

  /**
   * Sync preview display.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches behavior to sync the preview display when needed.
   */
  Drupal.behaviors.syncPreviewDisplay = {
    attach(context) {
      $(once('views-ajax', '#views-tabset a')).on('click', function () {
        const href = $(this).attr('href');
        // Cut of #views-tabset.
        const displayId = href.substring(11);
        const viewsPreviewId = document.querySelector(
          '#views-live-preview #preview-display-id',
        );
        if (viewsPreviewId) {
          // Set the form element if it is present.
          viewsPreviewId.value = displayId;
        }
      });
    },
  };

  /**
   * Ajax behaviors for the views_ui module.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches ajax behaviors to the elements with the classes in question.
   */
  Drupal.behaviors.viewsAjax = {
    collapseReplaced: false,
    attach(context, settings) {
      const baseElementSettings = {
        event: 'click',
        progress: { type: 'fullscreen' },
      };
      // Bind AJAX behaviors to all items showing the class.
      once('views-ajax', 'a.views-ajax-link', context).forEach((link) => {
        const $link = $(link);
        const elementSettings = baseElementSettings;
        elementSettings.base = $link.attr('id');
        elementSettings.element = link;
        // Set the URL to go to the anchor.
        if ($link.attr('href')) {
          elementSettings.url = $link.attr('href');
        }
        Drupal.ajax(elementSettings);
      });

      once('views-ajax', 'div#views-live-preview a').forEach((link) => {
        const $link = $(link);
        // We don't bind to links without a URL.
        if (!$link.attr('href')) {
          return true;
        }

        const elementSettings = baseElementSettings;
        // Set the URL to go to the anchor.
        elementSettings.url = $link.attr('href');
        if (
          Drupal.Views.getPath(elementSettings.url).substring(0, 21) !==
          'admin/structure/views'
        ) {
          return true;
        }

        elementSettings.wrapper = 'views-preview-wrapper';
        elementSettings.method = 'replaceWith';
        elementSettings.base = link.id;
        elementSettings.element = link;
        Drupal.ajax(elementSettings);
      });

      // Within a live preview, make exposed widget form buttons re-trigger the
      // Preview button.
      // @todo Revisit this after fixing Views UI to display a Preview outside
      //   of the main Edit form.
      once('views-ajax', 'div#views-live-preview input[type=submit]').forEach(
        (submit) => {
          const $submit = $(submit);
          $submit.on('click', function () {
            this.form.clk = this;
            return true;
          });
          const elementSettings = baseElementSettings;
          // Set the URL to go to the anchor.
          elementSettings.url = $(submit.form).attr('action');
          if (
            Drupal.Views.getPath(elementSettings.url).substring(0, 21) !==
            'admin/structure/views'
          ) {
            return true;
          }

          elementSettings.wrapper = 'views-preview-wrapper';
          elementSettings.method = 'replaceWith';
          elementSettings.event = 'click';
          elementSettings.base = submit.id;
          elementSettings.element = submit;

          Drupal.ajax(elementSettings);
        },
      );
    },
  };
})(jQuery, Drupal, drupalSettings);
