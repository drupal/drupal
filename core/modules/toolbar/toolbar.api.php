<?php

/**
 * @file
 * Hooks provided by the Toolbar module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Add items to the Toolbar menu.
 *
 * The Toolbar has two parts. The tabs are menu links, rendered by
 * theme_links(), that are displayed if the module is enabled and the user has
 * the 'access toolbar' permission. The trays are render elements, usually lists
 * of links, and each tray corresponds to a tab. When a tab is activated, the
 * corresponding tray is displayed; only one tab can be activated at a time. If
 * a tab does not have a corresponding tray, or if javascript is disabled, then
 * the tab is simply a link.
 *
 * This hook is invoked in toolbar_view().
 *
 * @return
 *   An array of toolbar items, keyed by unique identifiers such as 'home' or
 *   'administration', or the short name of the module implementing the hook.
 *   The corresponding value is an associative array that may contain the
 *   following key-value pairs:
 *   - 'tab': Required, unless the item is adding links to an existing tab. An
 *   array with keys 'title', 'href', 'html', and 'attributes', as used by
 *   theme_links(). The 'href' value is ignored unless 'tray' (below) is
 *   omitted, or if javascript is disabled.
 *   - 'tray': Optional. A render element that is displayed when the tab is
 *     activated.
 *   - 'weight': Optional. Integer weight used for sorting tabs.
 *
 * @see toolbar_view()
 * @ingroup toolbar_tabs
 */
function hook_toolbar() {
  $items = array();

  // The 'Home' tab is a simple link, with no corresponding tray.
  $items['home'] = array(
    'tab' => array(
      'title' => t('Home'),
      'href' => '<front>',
      'html' => FALSE,
      'attributes' => array(
        'title' => t('Home page'),
      ),
    ),
    'weight' => -10,
  );

  /**
   * A tab may be associated with a tray.
   *
   * The tray should contain a renderable array. An option #heading property
   * can be passed. The text is written to a heading tag in the tray as a
   * landmark for accessibility. A default heading will be created if one is not
   * passed.
   */
  $items['commerce'] = array(
    'tab' => array(
      'title' => t('Shopping cart'),
      'href' => '',
      'html' => FALSE,
      'attributes' => array(
        'title' => t('Shopping cart'),
      ),
    ),
    'tray' => array(
      '#heading' => t('Shopping cart actions'),
      'content' => array(
        '#theme' => 'item_list',
        '#items' => array( /* An item list renderable array */ ),
      ),
    ),
    'weight' => 50,
  );

  return $items;
}

/**
 * Alter the Toolbar menu after hook_toolbar() is invoked.
 *
 * This hook is invoked by toolbar_view() immediately after hook_toolbar(). The
 * toolbar definitions are passed in by reference. Each element of the $items
 * array is one item returned by a module from hook_toolbar(). Additional items
 * may be added, or existing items altered.
 *
 * @param $items
 *   Associative array of Toolbar menu definitions returned from hook_toolbar().
 */
function hook_toolbar_alter(&$items) {
  // Move the User tab to the right.
  $items['commerce']['weight'] = 5;
}

/**
 * @} End of "addtogroup hooks".
 */
