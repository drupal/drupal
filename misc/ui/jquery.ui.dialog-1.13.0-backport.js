/**
 * Backport of security fixes from:
 * https://bugs.jqueryui.com/ticket/6016
 * https://github.com/jquery/jquery-ui/pull/1635/files
 */

(function ($) {

  // Parts of this backport differ by jQuery version.
  var versionParts = $.ui.dialog.version.split('.');
  var majorVersion = parseInt(versionParts[0]);
  var minorVersion = parseInt(versionParts[1]);

  if (majorVersion === 1 && minorVersion < 13) {
    var _originalSetOption = $.ui.dialog.prototype._setOption;
    var _originalCreateTitlebar = $.ui.dialog.prototype._createTitlebar;

    $.extend($.ui.dialog.prototype, {

      _createTitlebar: function () {
        if (this.options.closeText) {
          this.options.closeText = Drupal.checkPlain(this.options.closeText);
        }
        _originalCreateTitlebar.apply(this, arguments);
      },

      _setOption: function (key, value) {
        if (key === 'title' || key == 'closeText') {
          if (value) {
            value = Drupal.checkPlain(value);
          }
        }
        _originalSetOption.apply(this, [key, value]);
      }
    });

    if (majorVersion === 1 && minorVersion < 10) {
      var _originalCreate = $.ui.dialog.prototype._create;

      $.extend($.ui.dialog.prototype, {

        _create: function () {
          if (!this.options.title) {
            var defaultTitle = this.element.attr('title');
            // .attr() might return a DOMElement
            if (typeof defaultTitle !== "string") {
              defaultTitle = "";
            }
            this.options.title = defaultTitle;
          }
          this.options.title = Drupal.checkPlain(this.options.title);
          _originalCreate.apply(this, arguments);
        },
      });
    }
  }

})(jQuery);
