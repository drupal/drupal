/**
 * @file
 * Handles AJAX submission and response in Views UI.
 */

(function ($, Drupal, drupalSettings) {

  "use strict";

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
  Drupal.AjaxCommands.prototype.viewsHighlight = function (ajax, response, status) {
    $('.hilited').removeClass('hilited');
    $(response.selector).addClass('hilited');
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
  Drupal.AjaxCommands.prototype.viewsShowButtons = function (ajax, response, status) {
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
  Drupal.AjaxCommands.prototype.viewsTriggerPreview = function (ajax, response, status) {
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
  Drupal.AjaxCommands.prototype.viewsReplaceTitle = function (ajax, response, status) {
    var doc = document;
    // For the <title> element, make a best-effort attempt to replace the page
    // title and leave the site name alone. If the theme doesn't use the site
    // name in the <title> element, this will fail.
    var oldTitle = doc.title;
    // Escape the site name, in case it has special characters in it, so we can
    // use it in our regex.
    var escapedSiteName = response.siteName.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
    var re = new RegExp('.+ (.) ' + escapedSiteName);
    doc.title = oldTitle.replace(re, response.title + ' $1 ' + response.siteName);

    $('h1.page-title').text(response.title);
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
    attach: function (context) {
      $('input#edit-displays-live-preview', context).once('views-ajax').on('click', function () {
        if ($(this).is(':checked')) {
          $('#preview-submit').trigger('click');
        }
      });
    }
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
    attach: function (context) {
      $("#views-tabset a").once('views-ajax').on('click', function () {
        var href = $(this).attr('href');
        // Cut of #views-tabset.
        var display_id = href.substr(11);
        // Set the form element.
        $("#views-live-preview #preview-display-id").val(display_id);
      });
    }
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
    attach: function (context, settings) {
      var base_element_settings = {
        event: 'click',
        progress: {type: 'fullscreen'}
      };
      // Bind AJAX behaviors to all items showing the class.
      $('a.views-ajax-link', context).once('views-ajax').each(function () {
        var element_settings = base_element_settings;
        element_settings.base = base;
        element_settings.element = this;
        // Set the URL to go to the anchor.
        if ($(this).attr('href')) {
          element_settings.url = $(this).attr('href');
        }
        var base = $(this).attr('id');
        Drupal.ajax(element_settings);
      });

      $('div#views-live-preview a')
        .once('views-ajax').each(function () {
          // We don't bind to links without a URL.
          if (!$(this).attr('href')) {
            return true;
          }

          var element_settings = base_element_settings;
          // Set the URL to go to the anchor.
          element_settings.url = $(this).attr('href');
          if (Drupal.Views.getPath(element_settings.url).substring(0, 21) !== 'admin/structure/views') {
            return true;
          }

          element_settings.wrapper = 'views-preview-wrapper';
          element_settings.method = 'replaceWith';
          element_settings.base = base;
          element_settings.element = this;
          var base = $(this).attr('id');
          Drupal.ajax(element_settings);
        });

      // Within a live preview, make exposed widget form buttons re-trigger the
      // Preview button.
      // @todo Revisit this after fixing Views UI to display a Preview outside
      //   of the main Edit form.
      $('div#views-live-preview input[type=submit]')
        .once('views-ajax').each(function (event) {
          $(this).on('click', function () {
            this.form.clk = this;
            return true;
          });
          var element_settings = base_element_settings;
          // Set the URL to go to the anchor.
          element_settings.url = $(this.form).attr('action');
          if (Drupal.Views.getPath(element_settings.url).substring(0, 21) !== 'admin/structure/views') {
            return true;
          }

          element_settings.wrapper = 'views-preview-wrapper';
          element_settings.method = 'replaceWith';
          element_settings.event = 'click';
          element_settings.base = base;
          element_settings.element = this;

          var base = $(this).attr('id');
          Drupal.ajax(element_settings);
        });

    }
  };

})(jQuery, Drupal, drupalSettings);
