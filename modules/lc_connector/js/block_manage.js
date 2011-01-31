// vim: set ts=2 sw=2 sts=2 et:

/**
 * @file
 * Block manage controller
 *
 * @category  Litecommerce connector
 * @package   Litecommerce connector
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @version   SVN: $Id: block_manage.js 2646 2010-04-16 07:50:13Z max $
 * @link      http://www.litecommerce.com/
 * @see       ____file_see____
 * @since     1.0.0
 */

(function ($) {

/**
 * Hide or show elements for different block types
 */
Drupal.behaviors.lcConnectorBlockType = {
  attach: function (context) {
    $('#edit-lc-block-type', context).bind('change', function () {

      var type = this.options[this.selectedIndex].value;

      if (type) {

        var bodyText = $('.text-format-wrapper');
        var widgetSettings = $('#edit-lc-widget-details');

        (bodyText && 'lc_widget' == type) ? bodyText.hide() : bodyText.show();
        (widgetSettings && 'lc_widget' != type) ? widgetSettings.hide() : widgetSettings.show();
      }

    }).change();
  }
};

/**
 * Hide or show elements for different widget classes
 */
Drupal.behaviors.lcConnectorWidgetClass = {
  attach: function (context) {
    $('#edit-lc-class', context).bind('change', function () {

      $('[id]').filter(function() { return this.id.match(/^lc_block_\w+/); }).css('display', 'none');

      var widgetClass = this.options[this.selectedIndex].value.replace(/\\/g, '_');

      if (widgetClass) {
        $('#lc_block_' + widgetClass.replace(/^_+/, '')).css('display', '');
      }

    }).change();
  }
};

})(jQuery);
