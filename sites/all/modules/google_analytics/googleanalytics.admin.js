// $Id: googleanalytics.admin.js,v 1.1.2.7 2011/02/07 15:20:57 hass Exp $
(function ($) {

/**
 * Provide the summary information for the tracking settings vertical tabs.
 */
Drupal.behaviors.trackingSettingsSummary = {
  attach: function (context) {
    // Make sure this behavior is processed only if drupalSetSummary is defined.
    if (typeof jQuery.fn.drupalSetSummary == 'undefined') {
      return;
    }

    $('fieldset#edit-page-vis-settings', context).drupalSetSummary(function (context) {
      var $radio = $('input[name="googleanalytics_visibility_pages"]:checked', context);
      if ($radio.val() == 0) {
        if (!$('textarea[name="googleanalytics_pages"]', context).val()) {
          return Drupal.t('Not restricted');
        }
        else {
          return Drupal.t('All pages with exceptions');
        }
      }
      else {
        return Drupal.t('Restricted to certain pages');
      }
    });

    $('fieldset#edit-role-vis-settings', context).drupalSetSummary(function (context) {
      var vals = [];
      $('input[type="checkbox"]:checked', context).each(function () {
        vals.push($.trim($(this).next('label').text()));
      });
      if (!vals.length) {
        return Drupal.t('Not restricted');
      }
      else if ($('input[name="googleanalytics_visibility_roles"]:checked', context).val() == 1) {
        return Drupal.t('Excepted: @roles', {'@roles' : vals.join(', ')});
      }
      else {
        return vals.join(', ');
      }
    });

    $('fieldset#edit-user-vis-settings', context).drupalSetSummary(function (context) {
      var $radio = $('input[name="googleanalytics_custom"]:checked', context);
      if ($radio.val() == 0) {
        return Drupal.t('Not customizable');
      }
      else if ($radio.val() == 1) {
        return Drupal.t('On by default with opt out');
      }
      else {
        return Drupal.t('Off by default with opt in');
      }
    });

    $('fieldset#edit-linktracking', context).drupalSetSummary(function (context) {
      var vals = [];
      if ($('input#edit-googleanalytics-trackoutbound', context).is(':checked')) {
        vals.push('Outbound links');
      }
      if ($('input#edit-googleanalytics-trackmailto', context).is(':checked')) {
        vals.push('Mailto links');
      }
      if ($('input#edit-googleanalytics-trackfiles', context).is(':checked')) {
        vals.push('Downloads');
      }
      if (!vals.length) {
        return Drupal.t('Not tracked');
      }
      return Drupal.t('@items tracked', {'@items' : vals.join(', ')});
    });

    $('fieldset#edit-search-and-adsense', context).drupalSetSummary(function (context) {
      var vals = [];
      if ($('input#edit-googleanalytics-site-search', context).is(':checked')) {
        vals.push('Site search');
      }
      if ($('input#edit-googleanalytics-trackadsense', context).is(':checked')) {
        vals.push('AdSense ads');
      }
      if (!vals.length) {
        return Drupal.t('Not tracked');
      }
      return Drupal.t('@items tracked', {'@items' : vals.join(', ')});
    });

    $('fieldset#edit-domain-tracking', context).drupalSetSummary(function (context) {
      var $radio = $('input[name="googleanalytics_domain_mode"]:checked', context);
      if ($radio.val() == 0) {
        return Drupal.t('A single domain');
      }
      else if ($radio.val() == 1) {
        return Drupal.t('One domain with multiple subdomains');
      }
      else {
        return Drupal.t('Multiple top-level domains');
      }
    });
  }
};

})(jQuery);
