<?php
// $Id$

/**
 * @file
 * Hooks provided by the Menu module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define menu items and page callbacks.
 *
 * This hook enables modules to register paths, which determines whose
 * requests are to be handled. Depending on the type of registration
 * requested by each path, a link is placed in the the navigation block and/or
 * an item appears in the menu administration page (q=admin/menu).
 *
 * This hook is called rarely - for example when modules are enabled.
 *
 * @return
 *   An array of menu items. Each menu item has a key corresponding to the
 *   Drupal path being registered. The item is an associative array that may
 *   contain the following key-value pairs:
 *   - "title": Required. The untranslated title of the menu item.
 *   - "title callback": Function to generate the title, defaults to t().
 *     If you require only the raw string to be output, set this to FALSE.
 *   - "title arguments": Arguments to send to t() or your custom callback.
 *   - "description": The untranslated description of the menu item.
 *   - "page callback": The function to call to display a web page when the user
 *     visits the path. If omitted, the parent menu item's callback will be used
 *     instead.
 *   - "page arguments": An array of arguments to pass to the page callback
 *     function. Integer values pass the corresponding URL component (see arg()).
 *   - "access callback": A  function returning a boolean value that determines
 *     whether the user has access rights to this menu item. Defaults to
 *     user_access() unless a value is inherited from a parent menu item.
 *   - "access arguments": An array of arguments to pass to the access callback
 *     function. Integer values pass the corresponding URL component.
 *   - "file": A file that will be included before the callbacks are accessed;
 *     this allows callback functions to be in separate files. The file should
 *     be relative to the implementing module's directory unless otherwise
 *     specified by the "file path" option.
 *   - "file path": The path to the folder containing the file specified in
 *     "file". This defaults to the path to the module implementing the hook.
 *   - "load arguments": An array of arguments to be passed to each of the
 *     object loaders in the path. For example, for the router item at
 *     node/%node/revisions/%/view, the array(1, 3) will call node_load() with
 *     the arguments corresponding to the second and fourth URL argument;
 *     as with other arguments, integers are automatically cast to URL
 *     arguments. There are also two "magic" values: "%index" will correspond
 *     to the URL index where the object's load function is specified; "%map"
 *     will correspond to the full menu map, passed in by reference to the
 *     load function.
 *   - "weight": An integer that determines relative position of items in the
 *     menu; higher-weighted items sink. Defaults to 0. When in doubt, leave
 *     this alone; the default alphabetical order is usually best.
 *   - "menu_name": Optional. Set this to a custom menu if you don't want your
 *     item to be placed in Navigation.
 *   - "type": A bitmask of flags describing properties of the menu item.
 *     Many shortcut bitmasks are provided as constants in menu.inc:
 *     - MENU_NORMAL_ITEM: Normal menu items show up in the menu tree and can be
 *       moved/hidden by the administrator.
 *     - MENU_CALLBACK: Callbacks simply register a path so that the correct
 *       function is fired when the URL is accessed.
 *     - MENU_SUGGESTED_ITEM: Modules may "suggest" menu items that the
 *       administrator may enable.
 *     - MENU_LOCAL_TASK: Local tasks are rendered as tabs by default.
 *     - MENU_DEFAULT_LOCAL_TASK: Every set of local tasks should provide one
 *       "default" task, that links to the same path as its parent when clicked.
 *     If the "type" key is omitted, MENU_NORMAL_ITEM is assumed.
 * For a detailed usage example, see page_example.module.
 * For comprehensive documentation on the menu system, see
 * http://drupal.org/node/102338.
 */
function hook_menu() {
  $items = array();

  $items['blog'] = array(
    'title' => 'blogs',
    'page callback' => 'blog_page',
    'access arguments' => array('access content'),
    'type' => MENU_SUGGESTED_ITEM,
  );
  $items['blog/feed'] = array(
    'title' => 'RSS feed',
    'page callback' => 'blog_feed',
    'access arguments' => array('access content'),
    'type' => MENU_CALLBACK,
  );

  return $items;
}

/**
 * Alter the data being saved to the {menu_router} table after hook_menu is invoked.
 *
 * This hook is invoked by menu_router_build(). The menu definitions are passed
 * in by reference. Each element of the $items array is one item returned
 * by a module from hook_menu. Additional items may be added, or existing items
 * altered.
 *
 * @param $items
 *   Associative array of menu router definitions returned from hook_menu().
 */
function hook_menu_alter(&$items) {
  // Example - disable the page at node/add
  $items['node/add']['access callback'] = FALSE;
}

/**
 * Alter the data being saved to the {menu_links} table by menu_link_save().
 *
 * @param $item
 *   Associative array defining a menu link as passed into menu_link_save().
 */
function hook_menu_link_alter(&$item) {
  // Example 1 - make all new admin links hidden (a.k.a disabled).
  if (strpos($item['link_path'], 'admin') === 0 && empty($item['mlid'])) {
    $item['hidden'] = 1;
  }
  // Example 2  - flag a link to be altered by hook_translated_menu_link_alter()
  if ($item['link_path'] == 'devel/cache/clear') {
    $item['options']['alter'] = TRUE;
  }
}

/**
 * Alter a menu link after it's translated, but before it's rendered.
 *
 * This hook may be used, for example, to add a page-specific query string.
 * For performance reasons, only links that have $item['options']['alter'] == TRUE
 * will be passed into this hook. The $item['options']['alter'] flag should
 * generally be set using hook_menu_link_alter().
 *
 * @param $item
 *   Associative array defining a menu link after _menu_link_translate()
 * @param $map
 *   Associative array containing the menu $map (path parts and/or objects).
 */
function hook_translated_menu_link_alter(&$item, $map) {
  if ($item['href'] == 'devel/cache/clear') {
    $item['localized_options']['query'] = drupal_get_destination();
  }
}

/**
 * @} End of "addtogroup hooks".
 */
