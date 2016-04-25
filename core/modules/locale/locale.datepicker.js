/**
 * @file
 * Datepicker JavaScript for the Locale module.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches language support to the jQuery UI datepicker component.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.localeDatepicker = {
    attach: function (context, settings) {
      // This code accesses drupalSettings and localized strings via Drupal.t().
      // So this code should run after these are initialized. By placing it in an
      // attach behavior this is assured.
      $.datepicker.regional['drupal-locale'] = $.extend({
        closeText: Drupal.t('Done'),
        prevText: Drupal.t('Prev'),
        nextText: Drupal.t('Next'),
        currentText: Drupal.t('Today'),
        monthNames: [
          Drupal.t('January', {}, {context: 'Long month name'}),
          Drupal.t('February', {}, {context: 'Long month name'}),
          Drupal.t('March', {}, {context: 'Long month name'}),
          Drupal.t('April', {}, {context: 'Long month name'}),
          Drupal.t('May', {}, {context: 'Long month name'}),
          Drupal.t('June', {}, {context: 'Long month name'}),
          Drupal.t('July', {}, {context: 'Long month name'}),
          Drupal.t('August', {}, {context: 'Long month name'}),
          Drupal.t('September', {}, {context: 'Long month name'}),
          Drupal.t('October', {}, {context: 'Long month name'}),
          Drupal.t('November', {}, {context: 'Long month name'}),
          Drupal.t('December', {}, {context: 'Long month name'})
        ],
        monthNamesShort: [
          Drupal.t('Jan'),
          Drupal.t('Feb'),
          Drupal.t('Mar'),
          Drupal.t('Apr'),
          Drupal.t('May'),
          Drupal.t('Jun'),
          Drupal.t('Jul'),
          Drupal.t('Aug'),
          Drupal.t('Sep'),
          Drupal.t('Oct'),
          Drupal.t('Nov'),
          Drupal.t('Dec')
        ],
        dayNames: [
          Drupal.t('Sunday'),
          Drupal.t('Monday'),
          Drupal.t('Tuesday'),
          Drupal.t('Wednesday'),
          Drupal.t('Thursday'),
          Drupal.t('Friday'),
          Drupal.t('Saturday')
        ],
        dayNamesShort: [
          Drupal.t('Sun'),
          Drupal.t('Mon'),
          Drupal.t('Tue'),
          Drupal.t('Wed'),
          Drupal.t('Thu'),
          Drupal.t('Fri'),
          Drupal.t('Sat')
        ],
        dayNamesMin: [
          Drupal.t('Su'),
          Drupal.t('Mo'),
          Drupal.t('Tu'),
          Drupal.t('We'),
          Drupal.t('Th'),
          Drupal.t('Fr'),
          Drupal.t('Sa')
        ],
        dateFormat: Drupal.t('mm/dd/yy'),
        firstDay: 0,
        isRTL: 0
      }, drupalSettings.jquery.ui.datepicker);
      $.datepicker.setDefaults($.datepicker.regional['drupal-locale']);
    }
  };

})(jQuery, Drupal, drupalSettings);
