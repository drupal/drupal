/**
 * Backport of security fix from:
 * https://github.com/jquery/jquery-ui/pull/1955/files
 */

(function ($) {

  // No backport is needed if we're already on jQuery UI 1.13 or higher.
  var versionParts = $.ui.version.split('.');
  var majorVersion = parseInt(versionParts[0]);
  var minorVersion = parseInt(versionParts[1]);
  if ( (majorVersion > 1) || (majorVersion === 1 && minorVersion >= 13) ) {
    return;
  }

  var fnOriginalPosition = $.fn.position;
  $.fn.extend({
    'position': function (options) {
      if (typeof options === 'undefined') {
        return fnOriginalPosition.call(this);
      }

      // Make sure string options are treated as CSS selectors
      var target = typeof options.of === "string" ?
        $(document).find(options.of) :
        $(options.of);

      options.of = (target[0] === undefined) ? null : target;
      return fnOriginalPosition.call(this, options);
    }
  });

})(jQuery);
