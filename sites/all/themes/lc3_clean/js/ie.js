/* vim: set ts=2 sw=2 sts=2 et: */

/**
 * Hacks and tweaks for IE6 and IE7 browsers
 *  
 * @author    Creative Development LLC <info@cdev.ru> 
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @version   SVN: $Id: ie.js 4693 2010-12-10 10:54:38Z xplorer $
 * @link      http://www.litecommerce.com/
 * @since     3.0.0
 */

/**
 * jQuery fix of "IE Z-Index bug" for ".menu-tree" menus
 *
 * See more info on this bug at http://css-discuss.incutio.com/?page=OverlappingAndZIndex
 */
$(function() {
  var zIndexNumber = 999999;
  $('.menu li').each(function() {
    $(this).css('zIndex', zIndexNumber--);
  });
});

/**
 * Emulation of CSS "border-spacing" for IE6 & IE7
 */
$(function() {
  $('table.products-grid').attr('cellspacing', '20px');
});

