/**
 * @file
 * Handles AJAX submission and response in Views UI.
 */

(function($, Drupal, drupalSettings) {
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
  Drupal.AjaxCommands.prototype.viewsHighlight = function(
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
  Drupal.AjaxCommands.prototype.viewsSetForm = function(
    ajax,
    response,
    status,
  ) {
    const $form = $('.js-views-ui-dialog form');
    // Identify the button that was clicked so that .ajaxSubmit() can use it.
    // We need to do this for both .click() and .mousedown() since JavaScript
    // code might trigger either behavior.
    const $submitButtons = $form
      .find('input[type=submit].js-form-submit, button.js-form-submit')
      .once('views-ajax-submit');
    $submitButtons.on('click mousedown', function() {
      this.form.clk = this;
    });
    $form.once('views-ajax-submit').each(function() {
      const $form = $(this);
      const elementSettings = {
        url: response.url,
        event: 'submit',
        base: $form.attr('id'),
        element: this,
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
   * @param {bool} response.changed
   *   Whether the state changed for the buttons or not.
   * @param {number} [status]
   *   The HTTP status code.
   */
  Drupal.AjaxCommands.prototype.viewsShowButtons = function(
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
  Drupal.AjaxCommands.prototype.viewsTriggerPreview = function(
    ajax,
    response,
    status,
  ) {
    if ($('input#edit-displays-live-preview').is(':checked')) {
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
  Drupal.AjaxCommands.prototype.viewsReplaceTitle = function(
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

    $('h1.page-title').text(response.title);
  };

  /**
   * Get rid of irritating tabledrag messages.
   *
   * @return {Array}
   *   An array of messages. Always empty array, to get rid of the messages.
   */
  Drupal.theme.tableDragChangedWarning = function() {
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
      $('input#edit-displays-live-preview', context)
        .once('views-ajax')
        .on('click', function() {
          if ($(this).is(':checked')) {
            $('#preview-submit').trigger('click');
          }
        });
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
      $('#views-tabset a')
        .once('views-ajax')
        .on('click', function() {
          const href = $(this).attr('href');
          // Cut of #views-tabset.
          const displayId = href.substr(11);
          // Set the form element.
          $('#views-live-preview #preview-display-id').val(displayId);
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
      $('a.views-ajax-link', context)
        .once('views-ajax')
        .each(function() {
          const elementSettings = baseElementSettings;
          elementSettings.base = $(this).attr('id');
          elementSettings.element = this;
          // Set the URL to go to the anchor.
          if ($(this).attr('href')) {
            elementSettings.url = $(this).attr('href');
          }
          Drupal.ajax(elementSettings);
        });

      $('div#views-live-preview a')
        .once('views-ajax')
        .each(function() {
          // We don't bind to links without a URL.
          if (!$(this).attr('href')) {
            return true;
          }

          const elementSettings = baseElementSettings;
          // Set the URL to go to the anchor.
          elementSettings.url = $(this).attr('href');
          if (
            Drupal.Views.getPath(elementSettings.url).substring(0, 21) !==
            'admin/structure/views'
          ) {
            return true;
          }

          elementSettings.wrapper = 'views-preview-wrapper';
          elementSettings.method = 'replaceWith';
          elementSettings.base = $(this).attr('id');
          elementSettings.element = this;
          Drupal.ajax(elementSettings);
        });

      // Within a live preview, make exposed widget form buttons re-trigger the
      // Preview button.
      // @todo Revisit this after fixing Views UI to display a Preview outside
      //   of the main Edit form.
      $('div#views-live-preview input[type=submit]')
        .once('views-ajax')
        .each(function(event) {
          $(this).on('click', function() {
            this.form.clk = this;
            return true;
          });
          const elementSettings = baseElementSettings;
          // Set the URL to go to the anchor.
          elementSettings.url = $(this.form).attr('action');
          if (
            Drupal.Views.getPath(elementSettings.url).substring(0, 21) !==
            'admin/structure/views'
          ) {
            return true;
          }

          elementSettings.wrapper = 'views-preview-wrapper';
          elementSettings.method = 'replaceWith';
          elementSettings.event = 'click';
          elementSettings.base = $(this).attr('id');
          elementSettings.element = this;

          Drupal.ajax(elementSettings);
        });
    },
  };
})(jQuery, Drupal, drupalSettings);
