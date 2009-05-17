/**
 * @file
 * Handles AJAX submission and response in Views UI.
 */
(function ($) {

  Drupal.ajax.prototype.commands.viewsSetForm = function (ajax, response, status) {
    var ajax_title = Drupal.settings.views.ajax.title;
    var ajax_body = Drupal.settings.views.ajax.id;
    var ajax_popup = Drupal.settings.views.ajax.popup;
    $(ajax_title).html(response.title);
    $(ajax_body).html(response.output);
    $(ajax_popup).dialog('open');
    Drupal.attachBehaviors($(ajax_popup), ajax.settings);
    if (response.url) {
      // Identify the button that was clicked so that .ajaxSubmit() can use it.
      // We need to do this for both .click() and .mousedown() since JavaScript
      // code might trigger either behavior.
      var $submit_buttons = $('input[type=submit], button', ajax_body);
      $submit_buttons.click(function(event) {
        this.form.clk = this;
      });
      $submit_buttons.mousedown(function(event) {
        this.form.clk = this;
      });

      $('form', ajax_body).once('views-ajax-submit-processed').each(function() {
        var element_settings = { 'url': response.url, 'event': 'submit', 'progress': { 'type': 'throbber' } };
        var $form = $(this);
        var id = $form.attr('id');
        Drupal.ajax[id] = new Drupal.ajax(id, this, element_settings);
        Drupal.ajax[id].form = $form;
      });
    }
    Drupal.viewsUi.resizeModal();
  };

  Drupal.ajax.prototype.commands.viewsDismissForm = function (ajax, response, status) {
    Drupal.ajax.prototype.commands.viewsSetForm({}, {'title': '', 'output': Drupal.settings.views.ajax.defaultForm});
    $(Drupal.settings.views.ajax.popup).dialog('close');
  }

  Drupal.ajax.prototype.commands.viewsHilite = function (ajax, response, status) {
    $('.hilited').removeClass('hilited');
    $(response.selector).addClass('hilited');
  };

  Drupal.ajax.prototype.commands.viewsAddTab = function (ajax, response, status) {
    var id = '#views-tab-' + response.id;
    $('#views-tabset').viewsAddTab(id, response.title, 0);
    $(id).html(response.body).addClass('views-tab');

    // Update the preview widget to preview the new tab.
    var display_id = id.replace('#views-tab-', '');
    $("#preview-display-id").append('<option selected="selected" value="' + display_id + '">' + response.title + '</option>');

    Drupal.attachBehaviors(id);
    var instance = $.viewsUi.tabs.instances[$('#views-tabset').get(0).UI_TABS_UUID];
    $('#views-tabset').viewsClickTab(instance.$tabs.length);
  };

  Drupal.ajax.prototype.commands.viewsShowButtons = function (ajax, response, status) {
    $('div.views-edit-view div.form-actions').removeClass('js-hide');
    $('div.views-edit-view div.view-changed.messages').removeClass('js-hide');
  };

  Drupal.ajax.prototype.commands.viewsTriggerPreview = function (ajax, response, status) {
    if ($('input#edit-displays-live-preview').is(':checked')) {
      $('#preview-submit').trigger('click');
    }
  };

  Drupal.ajax.prototype.commands.viewsReplaceTitle = function (ajax, response, status) {
    // In case we're in the overlay, get a reference to the underlying window.
    var doc = parent.document;
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
    $('h1#overlay-title').text(response.title);
  };

  /**
   * Get rid of irritating tabledrag messages
   */
  Drupal.theme.tableDragChangedWarning = function () {
    return [];
  }

  /**
   * Trigger preview when the "live preview" checkbox is checked.
   */
  Drupal.behaviors.livePreview = {
    attach: function (context) {
      $('input#edit-displays-live-preview', context).once('views-ajax-processed').click(function() {
        if ($(this).is(':checked')) {
          $('#preview-submit').click();
        }
      });
    }
  }

  /**
   * Sync preview display.
   */
  Drupal.behaviors.syncPreviewDisplay = {
    attach: function (context) {
      $("#views-tabset a").once('views-ajax-processed').click(function() {
        var href = $(this).attr('href');
        // Cut of #views-tabset.
        var display_id = href.substr(11);
        // Set the form element.
        $("#views-live-preview #preview-display-id").val(display_id);
      }).addClass('views-ajax-processed');
    }
  }

  Drupal.behaviors.viewsAjax = {
    collapseReplaced: false,
    attach: function (context, settings) {
      if (!settings.views) {
        return;
      }
      // Create a jQuery UI dialog, but leave it closed.
      var dialog_area = $(settings.views.ajax.popup, context);
      dialog_area.dialog({
        'autoOpen': false,
        'dialogClass': 'views-ui-dialog',
        'modal': true,
        'position': 'center',
        'resizable': false,
        'width': 750
      });

      var base_element_settings = {
        'event': 'click',
        'progress': { 'type': 'throbber' }
      };
      // Bind AJAX behaviors to all items showing the class.
      $('a.views-ajax-link', context).once('views-ajax-processed').each(function () {
        var element_settings = base_element_settings;
        // Set the URL to go to the anchor.
        if ($(this).attr('href')) {
          element_settings.url = $(this).attr('href');
        }
        var base = $(this).attr('id');
        Drupal.ajax[base] = new Drupal.ajax(base, this, element_settings);
      });

      $('div#views-live-preview a')
        .once('views-ajax-processed').each(function () {
        // We don't bind to links without a URL.
        if (!$(this).attr('href')) {
          return true;
        }

        var element_settings = base_element_settings;
        // Set the URL to go to the anchor.
        element_settings.url = $(this).attr('href');
        if (Drupal.Views.getPath(element_settings.url).substring(0, 21) != 'admin/structure/views') {
          return true;
        }

        element_settings.wrapper = 'views-live-preview';
        element_settings.method = 'html';
        var base = $(this).attr('id');
        Drupal.ajax[base] = new Drupal.ajax(base, this, element_settings);
      });

      // Within a live preview, make exposed widget form buttons re-trigger the
      // Preview button.
      // @todo Revisit this after fixing Views UI to display a Preview outside
      //   of the main Edit form.
      $('div#views-live-preview input[type=submit]')
        .once('views-ajax-processed').each(function(event) {
        $(this).click(function () {
          this.form.clk = this;
          return true;
        });
        var element_settings = base_element_settings;
        // Set the URL to go to the anchor.
        element_settings.url = $(this.form).attr('action');
        if (Drupal.Views.getPath(element_settings.url).substring(0, 21) != 'admin/structure/views') {
          return true;
        }

        element_settings.wrapper = 'views-live-preview';
        element_settings.method = 'html';
        element_settings.event = 'click';

        var base = $(this).attr('id');
        Drupal.ajax[base] = new Drupal.ajax(base, this, element_settings);
      });

      if (!this.collapseReplaced && Drupal.collapseScrollIntoView) {
        this.collapseReplaced = true;
        Drupal.collapseScrollIntoView = function (node) {
          for (var $parent = $(node); $parent.get(0) != document && $parent.size() != 0; $parent = $parent.parent()) {
            if ($parent.css('overflow') == 'scroll' || $parent.css('overflow') == 'auto') {
              if (Drupal.viewsUi.resizeModal) {
                // If the modal is already at the max height, don't bother with
                // this since the only reason to do it is to grow the modal.
                if ($('.views-ui-dialog').height() < parseInt($(window).height() * .8)) {
                  Drupal.viewsUi.resizeModal('', true);
                }
              }
              return;
            }
          }

          var h = document.documentElement.clientHeight || document.body.clientHeight || 0;
          var offset = document.documentElement.scrollTop || document.body.scrollTop || 0;
          var posY = $(node).offset().top;
          var fudge = 55;
          if (posY + node.offsetHeight + fudge > h + offset) {
            if (node.offsetHeight > h) {
              window.scrollTo(0, posY);
            }
            else {
              window.scrollTo(0, posY + node.offsetHeight - h + fudge);
            }
          }
        };
      }
    }
  };

})(jQuery);
