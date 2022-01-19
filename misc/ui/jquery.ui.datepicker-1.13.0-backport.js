/**
 * Backport of security fixes from:
 * https://github.com/jquery/jquery-ui/pull/1953
 * https://github.com/jquery/jquery-ui/pull/1954
 */

(function ($, Drupal) {

  // No backport is needed if we're already on jQuery UI 1.13 or higher.
  var versionParts = $.ui.datepicker.version.split('.');
  var majorVersion = parseInt(versionParts[0]);
  var minorVersion = parseInt(versionParts[1]);
  if ( (majorVersion > 1) || (majorVersion === 1 && minorVersion >= 13) ) {
    return;
  }

  var fnOriginalGet = $.datepicker._get;
  $.extend($.datepicker, {

    _get: function( inst, name ) {
      var val = fnOriginalGet.call(this, inst, name);

      // @see https://github.com/jquery/jquery-ui/pull/1954
      if (name === 'altField') {
        val = $(document).find(val);
      }
      // @see https://github.com/jquery/jquery-ui/pull/1953
      else if ($.inArray(name, ['appendText', 'buttonText', 'prevText', 'currentText', 'nextText', 'closeText']) !== -1) {
        val = Drupal.checkPlain(val);
      }

      return val;
    }

  })
})(jQuery, Drupal);
