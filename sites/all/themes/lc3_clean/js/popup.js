/* vim: set ts=2 sw=2 sts=2 et: */

/**
 * BlockUI-based popup
 *
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @version   SVN: $Id: popup.js 4779 2010-12-24 07:07:29Z xplorer $
 * @link      http://www.litecommerce.com/
 * @since     3.0.0
 */

// Display a ready-made block element
function lc3_clean_popup_div(id, fade)
{
  jQuery.blockUI.defaults.css = {};

  var selector = '#'+id;

  // Disable fade out in Linux versions of Google Chrome because jQuery renders it incorrectly in the browser
  var delay = (lc3_clean_is_linux_chrome() || !fade) ? 0 : 400;

  jQuery.blockUI(
    {
      message: jQuery(selector),
      fadeIn: delay,
      overlayCSS: {
        opacity: 1,
        background: '',
      }
    }
  );

  lc3_clean_postprocess_popup(id);

}

// Display block message
function lc3_clean_popup_message(data, id)
{
  jQuery.blockUI.defaults.css = {};

  // Disable fade out in Linux versions of Google Chrome because jQuery renders it incorrectly in the browser
  var delay = lc3_clean_is_linux_chrome() ? 0 : 400;

  jQuery.blockUI(
    {
      message: '<a href="#" class="close-link" onclick="javascript: blockUIPopupClose(); return false;"></a><div class="block-container"><div class="block-subcontainer">' + data + '</div></div>',
      fadeIn: delay,
      overlayCSS: {
        opacity: 0.7
      }
    }
  );

  lc3_clean_postprocess_popup(id);
}


// Close message box
function lc3_clean_close_popup()
{

  // Disable fade out in Linux versions of Google Chrome because jQuery renders it incorrectly in the browser
  var delay = lc3_clean_is_linux_chrome() ? 0 : 400;

  jQuery.unblockUI(
    {
      fadeOut: delay
    }
  );
}

// Checks whether it is a Linux Chrome browser
function lc3_clean_is_linux_chrome()
{
 return (navigator.userAgent.toLowerCase().indexOf('chrome') > -1) && (navigator.userAgent.toLowerCase().indexOf('linux') > -1);
}



// Postprocess a popup window
function lc3_clean_postprocess_popup(id)
{
  // Reposition
  var y = Math.round((jQuery(window).height() - jQuery('.blockMsg').height()) * 3/7);
  var x = Math.round((jQuery(window).width() - jQuery('.blockMsg').width()) / 2);
  if (y<0) {y = 0;}
  if (x<0) {x = 0;}
  jQuery('.blockMsg')
    .css('left', Math.round((jQuery(window).width() - jQuery('.blockMsg').width()) / 2) + 'px')
    .css('top', y+'px')
    .css('z-index', '1200000');

  // Modify overlay
  jQuery('.blockOverlay')
    .attr('title', 'Click to unblock')
    .css('z-index', '1100000')
    .css('cursor', 'pointer')
    .click(lc3_clean_close_popup);

  if (id) {
    var className = 'BlockMsg-' + id;
    jQuery('.blockMsg').addClass(className);
  }

}

