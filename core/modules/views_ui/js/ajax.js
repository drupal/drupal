/**
 * @file
 * Handles AJAX submission and response in Views UI.
 */
(function ($, Drupal, drupalSettings) {

  "use strict";

  Drupal.AjaxCommands.prototype.viewsHighlight = function (ajax, response, status) {
    $('.hilited').removeClass('hilited');
    $(response.selector).addClass('hilited');
  };

  Drupal.AjaxCommands.prototype.viewsShowButtons = function (ajax, response, status) {
    $('div.views-edit-view div.form-actions').removeClass('js-hide');
    $('div.views-edit-view div.view-changed.messages').removeClass('js-hide');
  };

  Drupal.AjaxCommands.prototype.viewsTriggerPreview = function (ajax, response, status) {
    if ($('input#edit-displays-live-preview').is(':checked')) {
      $('#preview-submit').trigger('click');
    }
  };

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
   * Get rid of irritating tabledrag messages
   */
  Drupal.theme.tableDragChangedWarning = function () {
    return [];
  };

  /**
   * Trigger preview when the "live preview" checkbox is checked.
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

  Drupal.behaviors.viewsAjax = {
    collapseReplaced: false,
    attach: function (context, settings) {
      var base_element_settings = {
        'event': 'click',
        'progress': { 'type': 'throbber' }
      };
      // Bind AJAX behaviors to all items showing the class.
      $('a.views-ajax-link', context).once('views-ajax').each(function () {
        var element_settings = base_element_settings;
        // Set the URL to go to the anchor.
        if ($(this).attr('href')) {
          element_settings.url = $(this).attr('href');
        }
        var base = $(this).attr('id');
        Drupal.ajax[base] = new Drupal.ajax(base, this, element_settings);
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
          var base = $(this).attr('id');
          Drupal.ajax[base] = new Drupal.ajax(base, this, element_settings);
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

          var base = $(this).attr('id');
          Drupal.ajax[base] = new Drupal.ajax(base, this, element_settings);
        });

    }
  };

})(jQuery, Drupal, drupalSettings);
