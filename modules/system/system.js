// $Id$
(function ($) {

/**
 * Show/hide the 'Email site administrator when updates are available' checkbox
 * on the install page.
 */
Drupal.hideEmailAdministratorCheckbox = function () {
  // Make sure the secondary box is shown / hidden as necessary on page load.
  if ($('#edit-update-status-module-1').is(':checked')) {
    $('.update-status-module-2-wrapper').show();
  }
  else {
    $('.update-status-module-2-wrapper').hide();
  }

  // Toggle the display as necessary when the checkbox is clicked.
  $('#edit-update-status-module-1').change( function () {
    $('.update-status-module-2-wrapper').toggle();
  })
};

/**
 * Internal function to check using Ajax if clean URLs can be enabled on the
 * settings page.
 *
 * This function is not used to verify whether or not clean URLs
 * are currently enabled.
 */
Drupal.behaviors.cleanURLsSettingsCheck = {
  attach: function (context, settings) {
    // This behavior attaches by ID, so is only valid once on a page.
    // Also skip if we are on an install page, as Drupal.cleanURLsInstallCheck will handle
    // the processing.
    if (!($('#edit-clean-url').size()) || $('.clean-url-processed, #edit-clean-url.install').size()) {
      return;
    }
    var url = settings.basePath + 'admin/settings/clean-urls/check';
    $.ajax({
      url: location.protocol + '//' + location.host + url,
      dataType: 'json',
      success: function () {
        // Check was successful. Redirect using a "clean URL". This will force the form that allows enabling clean URLs.
        location = settings.basePath +"admin/settings/clean-urls";
      }
    });
    $('#clean-url').addClass('clean-url-processed');
  }
};

/**
 * Internal function to check using Ajax if clean URLs can be enabled on the
 * install page.
 *
 * This function is not used to verify whether or not clean URLs
 * are currently enabled.
 */
Drupal.cleanURLsInstallCheck = function () {
  var url = location.protocol + '//' + location.host + Drupal.settings.basePath + 'admin/settings/clean-urls/check';
  // Submit a synchronous request to avoid database errors associated with
  // concurrent requests during install.
  $.ajax({
    async: false,
    url: url,
    dataType: 'json',
    success: function () {
      // Check was successful.
      $('#edit-clean-url').attr('value', 1);
    }
  });
  $('#edit-clean-url').addClass('clean-url-processed');
};

/**
 * When a field is filled out, apply its value to other fields that will likely
 * use the same value. In the installer this is used to populate the
 * administrator e-mail address with the same value as the site e-mail address.
 */
Drupal.behaviors.copyFieldValue = {
  attach: function (context, settings) {
    for (var sourceId in settings.copyFieldValue) {
      // Get the list of target fields.
      targetIds = settings.copyFieldValue[sourceId];
      if (!$('#'+ sourceId + '.copy-field-values-processed', context).size()) {
        // Add the behavior to update target fields on blur of the primary field.
        sourceField = $('#' + sourceId);
        sourceField.bind('blur', function () {
          for (var delta in targetIds) {
            var targetField = $('#'+ targetIds[delta]);
            if (targetField.val() == '') {
              targetField.val(this.value);
            }
          }
        });
        sourceField.addClass('copy-field-values-processed');
      }
    }
  }
};

/**
 * Show/hide custom format sections on the regional settings page.
 */
Drupal.behaviors.dateTime = {
  attach: function (context, settings) {
    // Show/hide custom format depending on the select's value.
    $('select.date-format:not(.date-time-processed)', context).change(function () {
      $(this).addClass('date-time-processed').parents('div.date-container').children('div.custom-container')[$(this).val() == 'custom' ? 'show' : 'hide']();
    });

    // Attach keyup handler to custom format inputs.
    $('input.custom-format:not(.date-time-processed)', context).addClass('date-time-processed').keyup(function () {
      var input = $(this);
      var url = settings.dateTime.lookup + (settings.dateTime.lookup.match(/\?q=/) ? '&format=' : '?format=') + encodeURIComponent(input.val());
      $.getJSON(url, function (data) {
        $('div.description span', input.parent()).html(data);
      });
    });

    // Trigger the event handler to show the form input if necessary.
    $('select.date-format', context).trigger('change');
  }
};

/**
 * Show/hide settings for user configurable time zones depending on whether
 * users are able to set their own time zones or not.
 */
Drupal.behaviors.userTimeZones = {
  attach: function (context, settings) {
    $('#empty-timezone-message-wrapper .description').hide();
    $('#edit-configurable-timezones', context).change(function () {
      $('#empty-timezone-message-wrapper').toggle();
    });
  },
};

/**
 * Show the powered by Drupal image preview
 */
Drupal.behaviors.poweredByPreview = {
  attach: function (context, settings) {
    $('#edit-color, #edit-size').change(function () {
      var path = settings.basePath + 'misc/' + $('#edit-color').val() + '-' + $('#edit-size').val() + '.png';
      $('img.powered-by-preview').attr('src', path);
    });
  }
};


})(jQuery);

