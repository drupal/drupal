(function ($) {

$.datepicker.regional['drupal-locale'] = {
  closeText: Drupal.t('Done'),
  prevText: Drupal.t('Prev'),
  nextText: Drupal.t('Next'),
  currentText: Drupal.t('Today'),
  monthNames: [
    Drupal.t('January'),
    Drupal.t('February'),
    Drupal.t('March'),
    Drupal.t('April'),
    Drupal.t('May'),
    Drupal.t('June'),
    Drupal.t('July'),
    Drupal.t('August'),
    Drupal.t('September'),
    Drupal.t('October'),
    Drupal.t('November'),
    Drupal.t('December')
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
  firstDay: Drupal.settings.jqueryuidatepicker.firstDay,
  isRTL: Drupal.settings.jqueryuidatepicker.rtl
};
$.datepicker.setDefaults($.datepicker.regional['drupal-locale']);

})(jQuery);
