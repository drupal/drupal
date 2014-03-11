<?php

/**
 * @file
 * API for the Drupal menu system.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\Language;
use Drupal\Core\Template\Attribute;
use Drupal\menu_link\MenuLinkInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * @defgroup menu Menu and routing system
 * @{
 * Define the navigation menus, and route page requests to code based on URLs.
 *
 * The Drupal routing system defines how Drupal responds to URLs passed to the
 * browser. The menu system, which depends on the routing system, is used for
 * navigation. The Menu module allows menus to be created in the user interface
 * as hierarchical lists of links.
 *
 * @section registering_paths Registering router paths
 * To register a path, you need to add lines similar to this in a
 * module.routing.yml file:
 * @code
 * block.admin_display:
 *   path: '/admin/structure/block'
 *   defaults:
 *     _content: '\Drupal\block\Controller\BlockListController::listing'
 *   requirements:
 *     _permission: 'administer blocks'
 * @endcode
 * @todo Add more information here, especially about controllers and what all
 *   the stuff in the routing.yml file means.
 *
 * @section Defining menu links
 * Once you have a route defined, you can use hook_menu_link_defaults() to
 * define links for your module's paths in the main Navigation menu or other
 * menus. See the hook_menu_link_defaults() documentation for more details.
 *
 * @todo The rest of this topic has not been reviewed or updated for Drupal 8.x
 *   and is not correct!
 * @todo It is quite likely that hook_menu() will be replaced with a different
 *   hook, configuration system, or plugin system before the 8.0 release.
 *
 * Drupal's menu system follows a simple hierarchy defined by paths.
 * Implementations of hook_menu() define menu items and assign them to
 * paths (which should be unique). The menu system aggregates these items
 * and determines the menu hierarchy from the paths. For example, if the
 * paths defined were a, a/b, e, a/b/c/d, f/g, and a/b/h, the menu system
 * would form the structure:
 * - a
 *   - a/b
 *     - a/b/c/d
 *     - a/b/h
 * - e
 * - f/g
 * Note that the number of elements in the path does not necessarily
 * determine the depth of the menu item in the tree.
 *
 * When responding to a page request, the menu system looks to see if the
 * path requested by the browser is registered as a menu item with a
 * callback. If not, the system searches up the menu tree for the most
 * complete match with a callback it can find. If the path a/b/i is
 * requested in the tree above, the callback for a/b would be used.
 *
 * The found callback function is called with any arguments specified
 * in the "page arguments" attribute of its menu item. The
 * attribute must be an array. After these arguments, any remaining
 * components of the path are appended as further arguments. In this
 * way, the callback for a/b above could respond to a request for
 * a/b/i differently than a request for a/b/j.
 *
 * For an illustration of this process, see page_example.module.
 *
 * Access to the callback functions is also protected by the menu system.
 * The "access callback" with an optional "access arguments" of each menu
 * item is called before the page callback proceeds. If this returns TRUE,
 * then access is granted; if FALSE, then access is denied. Default local task
 * menu items (see next paragraph) may omit this attribute to use the value
 * provided by the parent item.
 *
 * In the default Drupal interface, you will notice many links rendered as
 * tabs. These are known in the menu system as "local tasks", and they are
 * rendered as tabs by default, though other presentations are possible.
 * Local tasks function just as other menu items in most respects. It is
 * convention that the names of these tasks should be short verbs if
 * possible. In addition, a "default" local task should be provided for
 * each set. When visiting a local task's parent menu item, the default
 * local task will be rendered as if it is selected; this provides for a
 * normal tab user experience. This default task is special in that it
 * links not to its provided path, but to its parent item's path instead.
 * The default task's path is only used to place it appropriately in the
 * menu hierarchy.
 *
 * Everything described so far is stored in the menu_router table. The
 * menu_links table holds the visible menu links. By default these are
 * derived from the same hook_menu definitions, however you are free to
 * add more with menu_link_save().
 */

/**
 * @defgroup menu_flags Menu flags
 * @{
 * Flags for use in the "type" attribute of menu items.
 */

/**
 * Internal menu flag -- menu item is the root of the menu tree.
 */
const MENU_IS_ROOT = 0x0001;

/**
 * Internal menu flag -- menu item is visible in the menu tree.
 */
const MENU_VISIBLE_IN_TREE = 0x0002;

/**
 * Internal menu flag -- menu item links back to its parent.
 */
const MENU_LINKS_TO_PARENT = 0x0008;

/**
 * Internal menu flag -- menu item can be modified by administrator.
 */
const MENU_MODIFIED_BY_ADMIN = 0x0020;

/**
 * Internal menu flag -- menu item was created by administrator.
 */
const MENU_CREATED_BY_ADMIN = 0x0040;

/**
 * Internal menu flag -- menu item is a local task.
 */
const MENU_IS_LOCAL_TASK = 0x0080;

/**
 * @} End of "defgroup menu_flags".
 */

/**
 * @defgroup menu_item_types Menu item types
 * @{
 * Definitions for various menu item types.
 *
 * Menu item definitions provide one of these constants, which are shortcuts for
 * combinations of @link menu_flags Menu flags @endlink.
 */

/**
 * Menu type -- A "normal" menu item that's shown in menus.
 *
 * Normal menu items show up in the menu tree and can be moved/hidden by
 * the administrator. Use this for most menu items. It is the default value if
 * no menu item type is specified.
 */
define('MENU_NORMAL_ITEM', MENU_VISIBLE_IN_TREE);

/**
 * Menu type -- A hidden, internal callback, typically used for API calls.
 *
 * Callbacks simply register a path so that the correct function is fired
 * when the URL is accessed. They do not appear in menus.
 */
const MENU_CALLBACK = 0x0000;

/**
 * Menu type -- A normal menu item, hidden until enabled by an administrator.
 *
 * Modules may "suggest" menu items that the administrator may enable. They act
 * just as callbacks do until enabled, at which time they act like normal items.
 * Note for the value: 0x0010 was a flag which is no longer used, but this way
 * the values of MENU_CALLBACK and MENU_SUGGESTED_ITEM are separate.
 */
define('MENU_SUGGESTED_ITEM', 0x0010);

/**
 * @} End of "defgroup menu_item_types".
 */

/**
 * @defgroup menu_status_codes Menu status codes
 * @{
 * Status codes for menu callbacks.
 */

/**
 * Internal menu status code -- Menu item inaccessible because site is offline.
 */
const MENU_SITE_OFFLINE = 4;

/**
 * Internal menu status code -- Everything is working fine.
 */
const MENU_SITE_ONLINE = 5;

/**
 * @} End of "defgroup menu_status_codes".
 */

/**
 * @defgroup menu_tree_parameters Menu tree parameters
 * @{
 * Parameters for a menu tree.
 */

/**
 * The maximum depth of a menu links tree - matches the number of p columns.
 *
 * @todo Move this constant to MenuLinkStorageController along with all the tree
 * functionality.
 */
const MENU_MAX_DEPTH = 9;


/**
 * @} End of "defgroup menu_tree_parameters".
 */

/**
 * Reserved key to identify the most specific menu link for a given path.
 *
 * The value of this constant is a hash of the constant name. We use the hash
 * so that the reserved key is over 32 characters in length and will not
 * collide with allowed menu names:
 * @code
 * sha1('MENU_PREFERRED_LINK') = 1cf698d64d1aa4b83907cf6ed55db3a7f8e92c91
 * @endcode
 *
 * @see menu_link_get_preferred()
 */
const MENU_PREFERRED_LINK = '1cf698d64d1aa4b83907cf6ed55db3a7f8e92c91';

/**
 * Localizes a menu link title using t() if possible.
 *
 * Translate the title and description to allow storage of English title
 * strings in the database, yet display of them in the language required
 * by the current user.
 *
 * @param $item
 *   A menu link entity.
 */
function _menu_item_localize(&$item) {
  // Allow default menu links to be translated.
  $item['localized_options'] = $item['options'];
  // All 'class' attributes are assumed to be an array during rendering, but
  // links stored in the database may use an old string value.
  // @todo In order to remove this code we need to implement a database update
  //   including unserializing all existing link options and running this code
  //   on them, as well as adding validation to menu_link_save().
  if (isset($item['options']['attributes']['class']) && is_string($item['options']['attributes']['class'])) {
    $item['localized_options']['attributes']['class'] = explode(' ', $item['options']['attributes']['class']);
  }
  // If the menu link is defined in code and not customized, we can use t().
  if (!empty($item['machine_name']) && !$item['customized']) {
    // @todo Figure out a proper way to support translations of menu links, see
    //   https://drupal.org/node/2193777.
    $item['title'] = t($item['link_title']);
  }
  else {
    $item['title'] = $item['link_title'];
  }
}

/**
 * Provides menu link unserializing, access control, and argument handling.
 *
 * @param array $item
 *   The passed in item has the following keys:
 *   - access: (optional) Becomes TRUE if the item is accessible, FALSE
 *     otherwise. If the key is not set, the access manager is used to
 *     determine the access.
 *   - options: (required) Is unserialized and copied to $item['localized_options'].
 *   - link_title: (required) The title of the menu link.
 *   - route_name: (required) The route name of the menu link.
 *   - route_parameters: (required) The unserialized route parameters of the menu link.
 *   The passed in item is changed by the following keys:
 *   - href: The actual path to the link. This path is generated from the
 *     link_path of the menu link entity.
 *   - title: The title of the link. This title is generated from the
 *     link_title of the menu link entity.
 */
function _menu_link_translate(&$item) {
  if (!is_array($item['options'])) {
    $item['options'] = (array) unserialize($item['options']);
  }
  $item['localized_options'] = $item['options'];
  $item['title'] = $item['link_title'];
  if ($item['external'] || empty($item['route_name'])) {
    $item['access'] = 1;
    $item['href'] = $item['link_path'];
    $item['route_parameters'] = array();
    // Set to NULL so that drupal_pre_render_link() is certain to skip it.
    $item['route_name'] = NULL;
  }
  else {
    $item['href'] = NULL;
    if (!is_array($item['route_parameters'])) {
      $item['route_parameters'] = (array) unserialize($item['route_parameters']);
    }
    // menu_tree_check_access() may set this ahead of time for links to nodes.
    if (!isset($item['access'])) {
      $item['access'] = \Drupal::getContainer()->get('access_manager')->checkNamedRoute($item['route_name'], $item['route_parameters'], \Drupal::currentUser());
    }
    // For performance, don't localize a link the user can't access.
    if ($item['access']) {
      _menu_item_localize($item);
    }
  }

  // Allow other customizations - e.g. adding a page-specific query string to the
  // options array. For performance reasons we only invoke this hook if the link
  // has the 'alter' flag set in the options array.
  if (!empty($item['options']['alter'])) {
    \Drupal::moduleHandler()->alter('translated_menu_link', $item, $map);
  }
}

/**
 * Renders a menu tree based on the current path.
 *
 * @param $menu_name
 *   The name of the menu.
 *
 * @return
 *   A structured array representing the specified menu on the current page, to
 *   be rendered by drupal_render().
 */
function menu_tree($menu_name) {
  $menu_output = &drupal_static(__FUNCTION__, array());

  if (!isset($menu_output[$menu_name])) {
    $tree = menu_tree_page_data($menu_name);
    $menu_output[$menu_name] = menu_tree_output($tree);
  }
  return $menu_output[$menu_name];
}

/**
 * Returns an output structure for rendering a menu tree.
 *
 * The menu item's LI element is given one of the following classes:
 * - expanded: The menu item is showing its submenu.
 * - collapsed: The menu item has a submenu which is not shown.
 * - leaf: The menu item has no submenu.
 *
 * @param $tree
 *   A data structure representing the tree as returned from menu_tree_data.
 *
 * @return
 *   A structured array to be rendered by drupal_render().
 */
function menu_tree_output($tree) {
  $build = array();
  $items = array();

  // Pull out just the menu links we are going to render so that we
  // get an accurate count for the first/last classes.
  foreach ($tree as $data) {
    if ($data['link']['access'] && !$data['link']['hidden']) {
      $items[] = $data;
    }
  }

  foreach ($items as $data) {
    $class = array();
    // Set a class for the <li>-tag. Since $data['below'] may contain local
    // tasks, only set 'expanded' class if the link also has children within
    // the current menu.
    if ($data['link']['has_children'] && $data['below']) {
      $class[] = 'expanded';
    }
    elseif ($data['link']['has_children']) {
      $class[] = 'collapsed';
    }
    else {
      $class[] = 'leaf';
    }
    // Set a class if the link is in the active trail.
    if ($data['link']['in_active_trail']) {
      $class[] = 'active-trail';
      $data['link']['localized_options']['attributes']['class'][] = 'active-trail';
    }

    // Allow menu-specific theme overrides.
    $element['#theme'] = 'menu_link__' . strtr($data['link']['menu_name'], '-', '_');
    $element['#attributes']['class'] = $class;
    $element['#title'] = $data['link']['title'];
    // @todo Use route name and parameters to generate the link path, unless
    //    it is external.
    $element['#href'] = $data['link']['link_path'];
    $element['#localized_options'] = !empty($data['link']['localized_options']) ? $data['link']['localized_options'] : array();
    $element['#below'] = $data['below'] ? menu_tree_output($data['below']) : $data['below'];
    $element['#original_link'] = $data['link'];
    // Index using the link's unique mlid.
    $build[$data['link']['mlid']] = $element;
  }
  if ($build) {
    // Make sure drupal_render() does not re-order the links.
    $build['#sorted'] = TRUE;
    // Add the theme wrapper for outer markup.
    // Allow menu-specific theme overrides.
    $build['#theme_wrappers'][] = 'menu_tree__' . strtr($data['link']['menu_name'], '-', '_');
    // Set cache tag.
    $menu_name = $data['link']['menu_name'];
    $build['#cache']['tags']['menu'][$menu_name] = $menu_name;
  }

  return $build;
}

/**
 * Gets the data structure representing a named menu tree.
 *
 * Since this can be the full tree including hidden items, the data returned
 * may be used for generating an an admin interface or a select.
 *
 * @param $menu_name
 *   The named menu links to return
 * @param $link
 *   A fully loaded menu link, or NULL. If a link is supplied, only the
 *   path to root will be included in the returned tree - as if this link
 *   represented the current page in a visible menu.
 * @param $max_depth
 *   Optional maximum depth of links to retrieve. Typically useful if only one
 *   or two levels of a sub tree are needed in conjunction with a non-NULL
 *   $link, in which case $max_depth should be greater than $link['depth'].
 *
 * @return
 *   An tree of menu links in an array, in the order they should be rendered.
 */
function menu_tree_all_data($menu_name, $link = NULL, $max_depth = NULL) {
  $tree = &drupal_static(__FUNCTION__, array());
  $language_interface = \Drupal::languageManager()->getCurrentLanguage();

  // Use $mlid as a flag for whether the data being loaded is for the whole tree.
  $mlid = isset($link['mlid']) ? $link['mlid'] : 0;
  // Generate a cache ID (cid) specific for this $menu_name, $link, $language, and depth.
  $cid = 'links:' . $menu_name . ':all:' . $mlid . ':' . $language_interface->id . ':' . (int) $max_depth;

  if (!isset($tree[$cid])) {
    // If the static variable doesn't have the data, check {cache_menu}.
    $cache = \Drupal::cache('menu')->get($cid);
    if ($cache && isset($cache->data)) {
      // If the cache entry exists, it contains the parameters for
      // menu_build_tree().
      $tree_parameters = $cache->data;
    }
    // If the tree data was not in the cache, build $tree_parameters.
    if (!isset($tree_parameters)) {
      $tree_parameters = array(
        'min_depth' => 1,
        'max_depth' => $max_depth,
      );
      if ($mlid) {
        // The tree is for a single item, so we need to match the values in its
        // p columns and 0 (the top level) with the plid values of other links.
        $parents = array(0);
        for ($i = 1; $i < MENU_MAX_DEPTH; $i++) {
          if (!empty($link["p$i"])) {
            $parents[] = $link["p$i"];
          }
        }
        $tree_parameters['expanded'] = $parents;
        $tree_parameters['active_trail'] = $parents;
        $tree_parameters['active_trail'][] = $mlid;
      }

      // Cache the tree building parameters using the page-specific cid.
      \Drupal::cache('menu')->set($cid, $tree_parameters, Cache::PERMANENT, array('menu' => $menu_name));
    }

    // Build the tree using the parameters; the resulting tree will be cached
    // by _menu_build_tree()).
    $tree[$cid] = menu_build_tree($menu_name, $tree_parameters);
  }

  return $tree[$cid];
}

/**
 * Sets the path for determining the active trail of the specified menu tree.
 *
 * This path will also affect the breadcrumbs under some circumstances.
 * Breadcrumbs are built using the preferred link returned by
 * menu_link_get_preferred(). If the preferred link is inside one of the menus
 * specified in calls to menu_tree_set_path(), the preferred link will be
 * overridden by the corresponding path returned by menu_tree_get_path().
 *
 * Setting this path does not affect the main content; for that use
 * menu_set_active_item() instead.
 *
 * @param $menu_name
 *   The name of the affected menu tree.
 * @param $path
 *   The path to use when finding the active trail.
 */
function menu_tree_set_path($menu_name, $path = NULL) {
  $paths = &drupal_static(__FUNCTION__);
  if (isset($path)) {
    $paths[$menu_name] = $path;
  }
  return isset($paths[$menu_name]) ? $paths[$menu_name] : NULL;
}

/**
 * Gets the path for determining the active trail of the specified menu tree.
 *
 * @param $menu_name
 *   The menu name of the requested tree.
 *
 * @return
 *   A string containing the path. If no path has been specified with
 *   menu_tree_set_path(), NULL is returned.
 */
function menu_tree_get_path($menu_name) {
  return menu_tree_set_path($menu_name);
}

/**
 * Gets the data structure for a named menu tree, based on the current page.
 *
 * The tree order is maintained by storing each parent in an individual
 * field, see http://drupal.org/node/141866 for more.
 *
 * @param $menu_name
 *   The named menu links to return.
 * @param $max_depth
 *   (optional) The maximum depth of links to retrieve.
 * @param $only_active_trail
 *   (optional) Whether to only return the links in the active trail (TRUE)
 *   instead of all links on every level of the menu link tree (FALSE). Defaults
 *   to FALSE.
 *
 * @return
 *   An array of menu links, in the order they should be rendered. The array
 *   is a list of associative arrays -- these have two keys, link and below.
 *   link is a menu item, ready for theming as a link. Below represents the
 *   submenu below the link if there is one, and it is a subtree that has the
 *   same structure described for the top-level array.
 */
function menu_tree_page_data($menu_name, $max_depth = NULL, $only_active_trail = FALSE) {
  $tree = &drupal_static(__FUNCTION__, array());

  $language_interface = \Drupal::languageManager()->getCurrentLanguage();

  // Check if the active trail has been overridden for this menu tree.
  $active_path = menu_tree_get_path($menu_name);
  // Load the request corresponding to the current page.
  $request = \Drupal::request();
  $system_path = NULL;
  if ($route_name = $request->attributes->get(RouteObjectInterface::ROUTE_NAME)) {
    // @todo https://drupal.org/node/2068471 is adding support so we can tell
    // if this is called on a 404/403 page.
    $system_path = $request->attributes->get('_system_path');
    $page_not_403 = 1;
  }
  if (isset($system_path)) {
    if (isset($max_depth)) {
      $max_depth = min($max_depth, MENU_MAX_DEPTH);
    }
    // Generate a cache ID (cid) specific for this page.
    $cid = 'links:' . $menu_name . ':page:' . $system_path . ':' . $language_interface->id . ':' . $page_not_403 . ':' . (int) $max_depth;
    // If we are asked for the active trail only, and $menu_name has not been
    // built and cached for this page yet, then this likely means that it
    // won't be built anymore, as this function is invoked from
    // template_preprocess_page(). So in order to not build a giant menu tree
    // that needs to be checked for access on all levels, we simply check
    // whether we have the menu already in cache, or otherwise, build a minimum
    // tree containing the active trail only.
    // @see menu_set_active_trail()
    if (!isset($tree[$cid]) && $only_active_trail) {
      $cid .= ':trail';
    }

    if (!isset($tree[$cid])) {
      // If the static variable doesn't have the data, check {cache_menu}.
      $cache = \Drupal::cache('menu')->get($cid);
      if ($cache && isset($cache->data)) {
        // If the cache entry exists, it contains the parameters for
        // menu_build_tree().
        $tree_parameters = $cache->data;
      }
      // If the tree data was not in the cache, build $tree_parameters.
      if (!isset($tree_parameters)) {
        $tree_parameters = array(
          'min_depth' => 1,
          'max_depth' => $max_depth,
        );
        // Parent mlids; used both as key and value to ensure uniqueness.
        // We always want all the top-level links with plid == 0.
        $active_trail = array(0 => 0);

        // If this page is accessible to the current user, build the tree
        // parameters accordingly.
        if ($page_not_403) {
          // Find a menu link corresponding to the current path. If $active_path
          // is NULL, let menu_link_get_preferred() determine the path.
          if ($active_link = menu_link_get_preferred($active_path, $menu_name)) {
            // The active link may only be taken into account to build the
            // active trail, if it resides in the requested menu. Otherwise,
            // we'd needlessly re-run _menu_build_tree() queries for every menu
            // on every page.
            if ($active_link['menu_name'] == $menu_name) {
              // Use all the coordinates, except the last one because there
              // can be no child beyond the last column.
              for ($i = 1; $i < MENU_MAX_DEPTH; $i++) {
                if ($active_link['p' . $i]) {
                  $active_trail[$active_link['p' . $i]] = $active_link['p' . $i];
                }
              }
              // If we are asked to build links for the active trail only, skip
              // the entire 'expanded' handling.
              if ($only_active_trail) {
                $tree_parameters['only_active_trail'] = TRUE;
              }
            }
          }
          $parents = $active_trail;

          $expanded = \Drupal::state()->get('menu_expanded');
          // Check whether the current menu has any links set to be expanded.
          if (!$only_active_trail && $expanded && in_array($menu_name, $expanded)) {
            // Collect all the links set to be expanded, and then add all of
            // their children to the list as well.
            do {
              $query = \Drupal::entityQuery('menu_link')
                ->condition('menu_name', $menu_name)
                ->condition('expanded', 1)
                ->condition('has_children', 1)
                ->condition('plid', $parents, 'IN')
                ->condition('mlid', $parents, 'NOT IN');
              $result = $query->execute();
              $parents += $result;
            } while (!empty($result));
          }
          $tree_parameters['expanded'] = $parents;
          $tree_parameters['active_trail'] = $active_trail;
        }
        // If access is denied, we only show top-level links in menus.
        else {
          $tree_parameters['expanded'] = $active_trail;
          $tree_parameters['active_trail'] = $active_trail;
        }
        // Cache the tree building parameters using the page-specific cid.
        \Drupal::cache('menu')->set($cid, $tree_parameters, Cache::PERMANENT, array('menu' => $menu_name));
      }

      // Build the tree using the parameters; the resulting tree will be cached
      // by _menu_build_tree().
      $tree[$cid] = menu_build_tree($menu_name, $tree_parameters);
    }
    return $tree[$cid];
  }

  return array();
}

/**
 * Builds a menu tree, translates links, and checks access.
 *
 * @param $menu_name
 *   The name of the menu.
 * @param $parameters
 *   (optional) An associative array of build parameters. Possible keys:
 *   - expanded: An array of parent link ids to return only menu links that are
 *     children of one of the plids in this list. If empty, the whole menu tree
 *     is built, unless 'only_active_trail' is TRUE.
 *   - active_trail: An array of mlids, representing the coordinates of the
 *     currently active menu link.
 *   - only_active_trail: Whether to only return links that are in the active
 *     trail. This option is ignored, if 'expanded' is non-empty.
 *   - min_depth: The minimum depth of menu links in the resulting tree.
 *     Defaults to 1, which is the default to build a whole tree for a menu
 *     (excluding menu container itself).
 *   - max_depth: The maximum depth of menu links in the resulting tree.
 *   - conditions: An associative array of custom database select query
 *     condition key/value pairs; see _menu_build_tree() for the actual query.
 *
 * @return
 *   A fully built menu tree.
 */
function menu_build_tree($menu_name, array $parameters = array()) {
  // Build the menu tree.
  $data = _menu_build_tree($menu_name, $parameters);
  // Check access for the current user to each item in the tree.
  menu_tree_check_access($data['tree'], $data['node_links']);
  return $data['tree'];
}

/**
 * Builds a menu tree.
 *
 * This function may be used build the data for a menu tree only, for example
 * to further massage the data manually before further processing happens.
 * menu_tree_check_access() needs to be invoked afterwards.
 *
 * @see menu_build_tree()
 */
function _menu_build_tree($menu_name, array $parameters = array()) {
  // Static cache of already built menu trees.
  $trees = &drupal_static(__FUNCTION__, array());
  $language_interface = \Drupal::languageManager()->getCurrentLanguage();

  // Build the cache id; sort parents to prevent duplicate storage and remove
  // default parameter values.
  if (isset($parameters['expanded'])) {
    sort($parameters['expanded']);
  }
  $tree_cid = 'links:' . $menu_name . ':tree-data:' . $language_interface->id . ':' . hash('sha256', serialize($parameters));

  // If we do not have this tree in the static cache, check {cache_menu}.
  if (!isset($trees[$tree_cid])) {
    $cache = \Drupal::cache('menu')->get($tree_cid);
    if ($cache && isset($cache->data)) {
      $trees[$tree_cid] = $cache->data;
    }
  }

  if (!isset($trees[$tree_cid])) {
    $query = \Drupal::entityQuery('menu_link');
    for ($i = 1; $i <= MENU_MAX_DEPTH; $i++) {
      $query->sort('p' . $i, 'ASC');
    }
    $query->condition('menu_name', $menu_name);
    if (!empty($parameters['expanded'])) {
      $query->condition('plid', $parameters['expanded'], 'IN');
    }
    elseif (!empty($parameters['only_active_trail'])) {
      $query->condition('mlid', $parameters['active_trail'], 'IN');
    }
    $min_depth = (isset($parameters['min_depth']) ? $parameters['min_depth'] : 1);
    if ($min_depth != 1) {
      $query->condition('depth', $min_depth, '>=');
    }
    if (isset($parameters['max_depth'])) {
      $query->condition('depth', $parameters['max_depth'], '<=');
    }
    // Add custom query conditions, if any were passed.
    if (isset($parameters['conditions'])) {
      foreach ($parameters['conditions'] as $column => $value) {
        $query->condition($column, $value);
      }
    }

    // Build an ordered array of links using the query result object.
    $links = array();
    if ($result = $query->execute()) {
      $links = menu_link_load_multiple($result);
    }
    $active_trail = (isset($parameters['active_trail']) ? $parameters['active_trail'] : array());
    $data['tree'] = menu_tree_data($links, $active_trail, $min_depth);
    $data['node_links'] = array();
    menu_tree_collect_node_links($data['tree'], $data['node_links']);

    // Cache the data, if it is not already in the cache.
    \Drupal::cache('menu')->set($tree_cid, $data, Cache::PERMANENT, array('menu' => $menu_name));
    $trees[$tree_cid] = $data;
  }

  return $trees[$tree_cid];
}

/**
 * Collects node links from a given menu tree recursively.
 *
 * @param $tree
 *   The menu tree you wish to collect node links from.
 * @param $node_links
 *   An array in which to store the collected node links.
 */
function menu_tree_collect_node_links(&$tree, &$node_links) {
  foreach ($tree as $key => $v) {
    if ($tree[$key]['link']['router_path'] == 'node/%') {
      $nid = substr($tree[$key]['link']['link_path'], 5);
      if (is_numeric($nid)) {
        $node_links[$nid][$tree[$key]['link']['mlid']] = &$tree[$key]['link'];
        $tree[$key]['link']['access'] = FALSE;
      }
    }
    if ($tree[$key]['below']) {
      menu_tree_collect_node_links($tree[$key]['below'], $node_links);
    }
  }
}

/**
 * Checks access and performs dynamic operations for each link in the tree.
 *
 * @param $tree
 *   The menu tree you wish to operate on.
 * @param $node_links
 *   A collection of node link references generated from $tree by
 *   menu_tree_collect_node_links().
 */
function menu_tree_check_access(&$tree, $node_links = array()) {
  if ($node_links) {
    $nids = array_keys($node_links);
    $select = db_select('node_field_data', 'n');
    $select->addField('n', 'nid');
    // @todo This should be actually filtering on the desired node status field
    //   language and just fall back to the default language.
    $select->condition('n.status', 1);

    $select->condition('n.nid', $nids, 'IN');
    $select->addTag('node_access');
    $nids = $select->execute()->fetchCol();
    foreach ($nids as $nid) {
      foreach ($node_links[$nid] as $mlid => $link) {
        $node_links[$nid][$mlid]['access'] = TRUE;
      }
    }
  }
  _menu_tree_check_access($tree);
}

/**
 * Sorts the menu tree and recursively checks access for each item.
 */
function _menu_tree_check_access(&$tree) {
  $new_tree = array();
  foreach ($tree as $key => $v) {
    $item = &$tree[$key]['link'];
    _menu_link_translate($item);
    if ($item['access'] || ($item['in_active_trail'] && strpos($item['href'], '%') !== FALSE)) {
      if ($tree[$key]['below']) {
        _menu_tree_check_access($tree[$key]['below']);
      }
      // The weights are made a uniform 5 digits by adding 50000 as an offset.
      // After _menu_link_translate(), $item['title'] has the localized link title.
      // Adding the mlid to the end of the index insures that it is unique.
      $new_tree[(50000 + $item['weight']) . ' ' . $item['title'] . ' ' . $item['mlid']] = $tree[$key];
    }
  }
  // Sort siblings in the tree based on the weights and localized titles.
  ksort($new_tree);
  $tree = $new_tree;
}

/**
 * Sorts and returns the built data representing a menu tree.
 *
 * @param $links
 *   A flat array of menu links that are part of the menu. Each array element
 *   is an associative array of information about the menu link, containing the
 *   fields from the {menu_links} table, and optionally additional information
 *   from the {menu_router} table, if the menu item appears in both tables.
 *   This array must be ordered depth-first. See _menu_build_tree() for a sample
 *   query.
 * @param $parents
 *   An array of the menu link ID values that are in the path from the current
 *   page to the root of the menu tree.
 * @param $depth
 *   The minimum depth to include in the returned menu tree.
 *
 * @return
 *   An array of menu links in the form of a tree. Each item in the tree is an
 *   associative array containing:
 *   - link: The menu link item from $links, with additional element
 *     'in_active_trail' (TRUE if the link ID was in $parents).
 *   - below: An array containing the sub-tree of this item, where each element
 *     is a tree item array with 'link' and 'below' elements. This array will be
 *     empty if the menu item has no items in its sub-tree having a depth
 *     greater than or equal to $depth.
 */
function menu_tree_data(array $links, array $parents = array(), $depth = 1) {
  // Reverse the array so we can use the more efficient array_pop() function.
  $links = array_reverse($links);
  return _menu_tree_data($links, $parents, $depth);
}

/**
 * Builds the data representing a menu tree.
 *
 * The function is a bit complex because the rendering of a link depends on
 * the next menu link.
 */
function _menu_tree_data(&$links, $parents, $depth) {
  $tree = array();
  while ($item = array_pop($links)) {
    // We need to determine if we're on the path to root so we can later build
    // the correct active trail.
    $item['in_active_trail'] = in_array($item['mlid'], $parents);
    // Add the current link to the tree.
    $tree[$item['mlid']] = array(
      'link' => $item,
      'below' => array(),
    );
    // Look ahead to the next link, but leave it on the array so it's available
    // to other recursive function calls if we return or build a sub-tree.
    $next = end($links);
    // Check whether the next link is the first in a new sub-tree.
    if ($next && $next['depth'] > $depth) {
      // Recursively call _menu_tree_data to build the sub-tree.
      $tree[$item['mlid']]['below'] = _menu_tree_data($links, $parents, $next['depth']);
      // Fetch next link after filling the sub-tree.
      $next = end($links);
    }
    // Determine if we should exit the loop and return.
    if (!$next || $next['depth'] < $depth) {
      break;
    }
  }
  return $tree;
}

/**
 * Implements template_preprocess_HOOK() for theme_menu_tree().
 */
function template_preprocess_menu_tree(&$variables) {
  $variables['tree'] = $variables['tree']['#children'];
}

/**
 * Returns HTML for a wrapper for a menu sub-tree.
 *
 * @param $variables
 *   An associative array containing:
 *   - tree: An HTML string containing the tree's items.
 *
 * @see template_preprocess_menu_tree()
 * @ingroup themeable
 */
function theme_menu_tree($variables) {
  return '<ul class="menu">' . $variables['tree'] . '</ul>';
}

/**
 * Returns HTML for a menu link and submenu.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: Structured array data for a menu link.
 *
 * @ingroup themeable
 */
function theme_menu_link(array $variables) {
  $element = $variables['element'];
  $sub_menu = '';

  if ($element['#below']) {
    $sub_menu = drupal_render($element['#below']);
  }
  $element['#localized_options']['set_active_class'] = TRUE;
  $output = l($element['#title'], $element['#href'], $element['#localized_options']);
  return '<li' . new Attribute($element['#attributes']) . '>' . $output . $sub_menu . "</li>\n";
}

/**
 * Returns HTML for a single local task link.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render element containing:
 *     - #link: A menu link array with 'title', 'href', and 'localized_options'
 *       keys.
 *     - #active: A boolean indicating whether the local task is active.
 *
 * @ingroup themeable
 */
function theme_menu_local_task($variables) {
  $link = $variables['element']['#link'];
  $link += array(
    'localized_options' => array(),
  );
  $link_text = $link['title'];

  if (!empty($variables['element']['#active'])) {
    // Add text to indicate active tab for non-visual users.
    $active = '<span class="visually-hidden">' . t('(active tab)') . '</span>';

    // If the link does not contain HTML already, String::checkPlain() it now.
    // After we set 'html'=TRUE the link will not be sanitized by l().
    if (empty($link['localized_options']['html'])) {
      $link['title'] = String::checkPlain($link['title']);
    }
    $link['localized_options']['html'] = TRUE;
    $link_text = t('!local-task-title!active', array('!local-task-title' => $link['title'], '!active' => $active));
  }
  $link['localized_options']['set_active_class'] = TRUE;

  if (!empty($link['href'])) {
    // @todo - remove this once all pages are converted to routes.
    $a_tag = l($link_text, $link['href'], $link['localized_options']);
  }
  else {
    $a_tag = \Drupal::l($link_text, $link['route_name'], $link['route_parameters'], $link['localized_options']);
  }

  return '<li' . (!empty($variables['element']['#active']) ? ' class="active"' : '') . '>' . $a_tag . '</li>';
}

/**
 * Returns HTML for a single local action link.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: A render element containing:
 *     - #link: A menu link array with 'title', 'href', and 'localized_options'
 *       keys.
 *
 * @ingroup themeable
 */
function theme_menu_local_action($variables) {
  $link = $variables['element']['#link'];
  $link += array(
    'href' => '',
    'localized_options' => array(),
    'route_parameters' => array(),
  );
  $link['localized_options']['attributes']['class'][] = 'button';
  $link['localized_options']['attributes']['class'][] = 'button-action';
  $link['localized_options']['set_active_class'] = TRUE;

  $output = '<li>';
  // @todo Remove this check and the call to l() when all pages are converted to
  //   routes.
  // @todo Figure out how to support local actions without a href properly.
  if ($link['href'] === '' && !empty($link['route_name'])) {
    $output .= Drupal::l($link['title'], $link['route_name'], $link['route_parameters'], $link['localized_options']);
  }
  else {
    $output .= l($link['title'], $link['href'], $link['localized_options']);
  }
  $output .= "</li>";

  return $output;
}

/**
 * Generates elements for the $arg array in the help hook.
 */
function drupal_help_arg($arg = array()) {
  // Note - the number of empty elements should be > MENU_MAX_PARTS.
  return $arg + array('', '', '', '', '', '', '', '', '', '', '', '');
}

/**
 * Returns an array containing the names of system-defined (default) menus.
 */
function menu_list_system_menus() {
  return array(
    'tools' => 'Tools',
    'admin' => 'Administration',
    'account' => 'User account menu',
    'main' => 'Main navigation',
    'footer' => 'Footer menu',
  );
}

/**
 * Returns an array of links to be rendered as the Main menu.
 */
function menu_main_menu() {
  $main_links_source = _menu_get_links_source('main_links', 'main');
  return menu_navigation_links($main_links_source);
}

/**
 * Returns an array of links to be rendered as the Secondary links.
 */
function menu_secondary_menu() {
  $main_links_source = _menu_get_links_source('main_links', 'main');
  $secondary_links_source = _menu_get_links_source('secondary_links', 'account');

  // If the secondary menu source is set as the primary menu, we display the
  // second level of the primary menu.
  if ($secondary_links_source == $main_links_source) {
    return menu_navigation_links($main_links_source, 1);
  }
  else {
    return menu_navigation_links($secondary_links_source, 0);
  }
}

/**
 * Returns the source of links of a menu.
 *
 * @param string $name
 *   A string configuration key of menu link source.
 * @param string $default
 *   Default menu name.
 *
 * @return string
 *   Returns menu name, if exist
 */
function _menu_get_links_source($name, $default) {
  $config = \Drupal::config('menu.settings');
  return \Drupal::moduleHandler()->moduleExists('menu') ? $config->get($name) : $default;
}

/**
 * Returns an array of links for a navigation menu.
 *
 * @param $menu_name
 *   The name of the menu.
 * @param $level
 *   Optional, the depth of the menu to be returned.
 *
 * @return
 *   An array of links of the specified menu and level.
 */
function menu_navigation_links($menu_name, $level = 0) {
  // Don't even bother querying the menu table if no menu is specified.
  if (empty($menu_name)) {
    return array();
  }

  // Get the menu hierarchy for the current page.
  $tree = menu_tree_page_data($menu_name, $level + 1);

  // Go down the active trail until the right level is reached.
  while ($level-- > 0 && $tree) {
    // Loop through the current level's items until we find one that is in trail.
    while ($item = array_shift($tree)) {
      if ($item['link']['in_active_trail']) {
        // If the item is in the active trail, we continue in the subtree.
        $tree = empty($item['below']) ? array() : $item['below'];
        break;
      }
    }
  }

  // Create a single level of links.
  $links = array();
  foreach ($tree as $item) {
    if (!$item['link']['hidden']) {
      $class = '';
      $l = $item['link']['localized_options'];
      $l['href'] = $item['link']['link_path'];
      $l['title'] = $item['link']['title'];
      if ($item['link']['in_active_trail']) {
        $class = ' active-trail';
        $l['attributes']['class'][] = 'active-trail';
      }
      // Normally, l() compares the href of every link with the current path and
      // sets the active class accordingly. But local tasks do not appear in
      // menu trees, so if the current path is a local task, and this link is
      // its tab root, then we have to set the class manually.
      if ($item['link']['href'] != current_path()) {
        $l['attributes']['class'][] = 'active';
      }
      // Keyed with the unique mlid to generate classes in links.html.twig.
      $links['menu-' . $item['link']['mlid'] . $class] = $l;
    }
  }
  return $links;
}

/**
 * Collects the local tasks (tabs), action links, and the root path.
 *
 * @param int $level
 *   The level of tasks you ask for. Primary tasks are 0, secondary are 1.
 *
 * @return array
 *   An array containing
 *   - tabs: Local tasks for the requested level.
 *   - actions: Action links for the requested level.
 *   - root_path: The router path for the current page. If the current page is
 *     a default local task, then this corresponds to the parent tab.
 *
 * @see hook_menu_local_tasks()
 * @see hook_menu_local_tasks_alter()
 */
function menu_local_tasks($level = 0) {
  $data = &drupal_static(__FUNCTION__);
  $root_path = &drupal_static(__FUNCTION__ . ':root_path', '');
  $empty = array(
    'tabs' => array(),
    'actions' => array(),
    'root_path' => &$root_path,
  );

  if (!isset($data)) {
    // Look for route-based tabs.
    $data['tabs'] = array();
    $data['actions'] = array();

    $route_name = \Drupal::request()->attributes->get(RouteObjectInterface::ROUTE_NAME);
    if (!empty($route_name)) {
      $manager = \Drupal::service('plugin.manager.menu.local_task');
      $local_tasks = $manager->getTasksBuild($route_name);
      foreach ($local_tasks as $level => $items) {
        $data['tabs'][$level] = empty($data['tabs'][$level]) ? $items : array_merge($data['tabs'][$level], $items);
      }
    }

    // Allow modules to dynamically add further tasks.
    $module_handler = \Drupal::moduleHandler();
    foreach ($module_handler->getImplementations('menu_local_tasks') as $module) {
      $function = $module . '_menu_local_tasks';
      $function($data, $route_name);
    }
    // Allow modules to alter local tasks.
    $module_handler->alter('menu_local_tasks', $data, $route_name);
  }

  if (isset($data['tabs'][$level])) {
    return array(
      'tabs' => $data['tabs'][$level],
      'actions' => $data['actions'],
      'root_path' => $root_path,
    );
  }
  elseif (!empty($data['actions'])) {
    return array('actions' => $data['actions']) + $empty;
  }
  return $empty;
}

/**
 * Returns the rendered local tasks at the top level.
 */
function menu_primary_local_tasks() {
  $links = menu_local_tasks(0);
  // Do not display single tabs.
  return count(element_get_visible_children($links['tabs'])) > 1 ? $links['tabs'] : '';
}

/**
 * Returns the rendered local tasks at the second level.
 */
function menu_secondary_local_tasks() {
  $links = menu_local_tasks(1);
  // Do not display single tabs.
  return count(element_get_visible_children($links['tabs'])) > 1 ? $links['tabs'] : '';
}

/**
 * Returns the rendered local actions at the current level.
 */
function menu_get_local_actions() {
  $links = menu_local_tasks();
  $route_name = Drupal::request()->attributes->get(RouteObjectInterface::ROUTE_NAME);
  $manager = \Drupal::service('plugin.manager.menu.local_action');
  return $manager->getActionsForRoute($route_name) + $links['actions'];
}

/**
 * Returns the router path, or the path for a default local task's parent.
 */
function menu_tab_root_path() {
  $links = menu_local_tasks();
  return $links['root_path'];
}

/**
 * Returns a renderable element for the primary and secondary tabs.
 */
function menu_local_tabs() {
  $build = array(
    '#theme' => 'menu_local_tasks',
    '#primary' => menu_primary_local_tasks(),
    '#secondary' => menu_secondary_local_tasks(),
  );
  return !empty($build['#primary']) || !empty($build['#secondary']) ? $build : array();
}

/**
 * Returns HTML for primary and secondary local tasks.
 *
 * @param $variables
 *   An associative array containing:
 *     - primary: (optional) An array of local tasks (tabs).
 *     - secondary: (optional) An array of local tasks (tabs).
 *
 * @ingroup themeable
 * @see menu_local_tasks()
 */
function theme_menu_local_tasks(&$variables) {
  $output = '';

  if (!empty($variables['primary'])) {
    $variables['primary']['#prefix'] = '<h2 class="visually-hidden">' . t('Primary tabs') . '</h2>';
    $variables['primary']['#prefix'] .= '<ul class="tabs primary">';
    $variables['primary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['primary']);
  }
  if (!empty($variables['secondary'])) {
    $variables['secondary']['#prefix'] = '<h2 class="visually-hidden">' . t('Secondary tabs') . '</h2>';
    $variables['secondary']['#prefix'] .= '<ul class="tabs secondary">';
    $variables['secondary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['secondary']);
  }

  return $output;
}

/**
 * Sets (or gets) the active menu for the current page.
 *
 * The active menu for the page determines the active trail.
 *
 * @return
 *   An array of menu machine names, in order of preference. The
 *   'system.menu:active_menus_default' config item may be used to assert a menu
 *   order different from the order of creation, or to prevent a particular menu
 *   from being used at all in the active trail.
 */
function menu_set_active_menu_names($menu_names = NULL) {
  $active = &drupal_static(__FUNCTION__);

  if (isset($menu_names) && is_array($menu_names)) {
    $active = $menu_names;
  }
  elseif (!isset($active)) {
    $config = \Drupal::config('system.menu');
    $active = $config->get('active_menus_default') ?: array_keys(menu_list_system_menus());
  }
  return $active;
}

/**
 * Gets the active menu for the current page.
 */
function menu_get_active_menu_names() {
  return menu_set_active_menu_names();
}

/**
 * Sets the active path, which determines which page is loaded.
 *
 * Note that this may not have the desired effect unless invoked very early
 * in the page load or unless you do a subrequest to generate your page output.
 *
 * @param $path
 *   A Drupal path - not a path alias.
 */
function menu_set_active_item($path) {
  // Since the active item has changed, the active menu trail may also be out
  // of date.
  drupal_static_reset('menu_set_active_trail');
  // @todo Refactor to use the Symfony Request object.
  _current_path($path);
}

/**
 * Sets the active trail (path to the menu tree root) of the current page.
 *
 * Any trail set by this function will only be used for functionality that calls
 * menu_get_active_trail(). Drupal core only uses trails set here for
 * the page title and not for menu trees or page content.
 *
 * To affect the trail used by menu trees, use menu_tree_set_path(). To affect
 * the page content, use menu_set_active_item() instead.
 *
 * @param $new_trail
 *   Menu trail to set; the value is saved in a static variable and can be
 *   retrieved by menu_get_active_trail(). The format of this array should be
 *   the same as the return value of menu_get_active_trail().
 *
 * @return
 *   The active trail. See menu_get_active_trail() for details.
 */
function menu_set_active_trail($new_trail = NULL) {
  $trail = &drupal_static(__FUNCTION__);

  if (isset($new_trail)) {
    $trail = $new_trail;
  }
  elseif (!isset($trail)) {
    $trail = array();
    $trail[] = array(
      'title' => t('Home'),
      'href' => '<front>',
      'link_path' => '',
      'localized_options' => array(),
      'type' => 0,
    );

    // Try to retrieve a menu link corresponding to the current path. If more
    // than one exists, the link from the most preferred menu is returned.
    $preferred_link = menu_link_get_preferred();

    // There is a link for the current path.
    if ($preferred_link) {
       _menu_link_translate($preferred_link);
      // Pass TRUE for $only_active_trail to make menu_tree_page_data() build
      // a stripped down menu tree containing the active trail only, in case
      // the given menu has not been built in this request yet.
      $tree = menu_tree_page_data($preferred_link['menu_name'], NULL, TRUE);
      list($key, $curr) = each($tree);
    }
    // There is no link for the current path.
    else {
      $curr = FALSE;
    }

    while ($curr) {
      $link = $curr['link'];
      if ($link['in_active_trail']) {
        // Add the link to the trail, unless it links to its parent.
        if (!($link['type'] & MENU_LINKS_TO_PARENT)) {
          // The menu tree for the active trail may contain additional links
          // that have not been translated yet, since they contain dynamic
          // argument placeholders (%). Such links are not contained in regular
          // menu trees, and have only been loaded for the additional
          // translation that happens here, so as to be able to display them in
          // the breadcumb for the current page.
          // @see _menu_tree_check_access()
          // @see _menu_link_translate()
          if (strpos($link['href'], '%') !== FALSE) {
            _menu_link_translate($link);
          }
          if ($link['access']) {
            $trail[] = $link;
          }
        }
        $tree = $curr['below'] ? $curr['below'] : array();
      }
      list($key, $curr) = each($tree);
    }
    // Make sure the current page is in the trail to build the page title, by
    // appending either the preferred link or the menu router item for the
    // current page. Exclude it if we are on the front page.
    $last = end($trail);
    if ($preferred_link && $last['href'] != $preferred_link['href'] && !drupal_is_front_page()) {
      $trail[] = $preferred_link;
    }
  }
  return $trail;
}

/**
 * Looks up the preferred menu link for a given system path.
 *
 * @param $path
 *   The path; for example, 'node/5'. The function will find the corresponding
 *   menu link ('node/5' if it exists, or fallback to 'node/%').
 * @param $selected_menu
 *   The name of a menu used to restrict the search for a preferred menu link.
 *   If not specified, all the menus returned by menu_get_active_menu_names()
 *   will be used.
 *
 * @return
 *   A fully translated menu link, or FALSE if no matching menu link was
 *   found. The most specific menu link ('node/5' preferred over 'node/%') in
 *   the most preferred menu (as defined by menu_get_active_menu_names()) is
 *   returned.
 */
function menu_link_get_preferred($path = NULL, $selected_menu = NULL) {
  $preferred_links = &drupal_static(__FUNCTION__);

  if (!isset($path)) {
    $path = current_path();
  }

  if (empty($selected_menu)) {
    // Use an illegal menu name as the key for the preferred menu link.
    $selected_menu = MENU_PREFERRED_LINK;
  }

  if (!isset($preferred_links[$path])) {
    // Look for the correct menu link by building a list of candidate paths,
    // which are ordered by priority (translated hrefs are preferred over
    // untranslated paths). Afterwards, the most relevant path is picked from
    // the menus, ordered by menu preference.
    $path_candidates = array();
    // 1. The current item href.
    // @todo simplify this code and convert to using route names.
    // @see https://drupal.org/node/2154949
    $path_candidates[$path] = $path;

    // Retrieve a list of menu names, ordered by preference.
    $menu_names = menu_get_active_menu_names();
    // Put the selected menu at the front of the list.
    array_unshift($menu_names, $selected_menu);

    $menu_links = entity_load_multiple_by_properties('menu_link', array('link_path' => $path_candidates));

    // Sort candidates by link path and menu name.
    $candidates = array();
    foreach ($menu_links as $candidate) {
      $candidates[$candidate['link_path']][$candidate['menu_name']] = $candidate;
      // Add any menus not already in the menu name search list.
      if (!in_array($candidate['menu_name'], $menu_names)) {
        $menu_names[] = $candidate['menu_name'];
      }
    }

    // Store the most specific link for each menu. Also save the most specific
    // link of the most preferred menu in $preferred_link.
    foreach ($path_candidates as $link_path) {
      if (isset($candidates[$link_path])) {
        foreach ($menu_names as $menu_name) {
          if (empty($preferred_links[$path][$menu_name]) && isset($candidates[$link_path][$menu_name])) {
            $candidate_item = $candidates[$link_path][$menu_name];
            $candidate_item['access'] = \Drupal::service('access_manager')->checkNamedRoute($candidate_item['route_name'], $candidate_item['route_parameters'], \Drupal::currentUser());
            if ($candidate_item['access']) {
              _menu_item_localize($candidate_item);
              $preferred_links[$path][$menu_name] = $candidate_item;
              if (empty($preferred_links[$path][MENU_PREFERRED_LINK])) {
                // Store the most specific link.
                $preferred_links[$path][MENU_PREFERRED_LINK] = $candidate_item;
              }
            }
          }
        }
      }
    }
  }

  return isset($preferred_links[$path][$selected_menu]) ? $preferred_links[$path][$selected_menu] : FALSE;
}

/**
 * Gets the active trail (path to root menu root) of the current page.
 *
 * If a trail is supplied to menu_set_active_trail(), that value is returned. If
 * a trail is not supplied to menu_set_active_trail(), the path to the current
 * page is calculated and returned. The calculated trail is also saved as a
 * static value for use by subsequent calls to menu_get_active_trail().
 *
 * @return
 *   Path to menu root of the current page, as an array of menu link items,
 *   starting with the site's home page. Each link item is an associative array
 *   with the following components:
 *   - title: Title of the item.
 *   - href: Drupal path of the item.
 *   - localized_options: Options for passing into the l() function.
 *   - type: A menu type constant, such as MENU_DEFAULT_LOCAL_TASK, or 0 to
 *     indicate it's not really in the menu (used for the home page item).
 */
function menu_get_active_trail() {
  return menu_set_active_trail();
}

/**
 * Clears all cached menu data.
 *
 * This should be called any time broad changes
 * might have been made to the router items or menu links.
 */
function menu_cache_clear_all() {
  \Drupal::cache('menu')->deleteAll();
  menu_reset_static_cache();
}

/**
 * Resets the menu system static cache.
 */
function menu_reset_static_cache() {
  \Drupal::entityManager()
    ->getStorageController('menu_link')->resetCache();
  drupal_static_reset('_menu_build_tree');
  drupal_static_reset('menu_tree');
  drupal_static_reset('menu_tree_all_data');
  drupal_static_reset('menu_tree_page_data');
  drupal_static_reset('menu_link_get_preferred');
}

/**
 * Saves menu links recursively for menu_links_rebuild_defaults().
 */
function _menu_link_save_recursive($controller, $machine_name, &$children, &$links) {
  $menu_link = $links[$machine_name];
  if ($menu_link->isNew() || !$menu_link->customized) {
    if (!isset($menu_link->plid) && !empty($menu_link->parent) && !empty($links[$menu_link->parent])) {
      $parent = $links[$menu_link->parent];

      if (empty($menu_link->menu_name) || $parent->menu_name == $menu_link->menu_name) {
        $menu_link->plid = $parent->id();
        $menu_link->menu_name = $parent->menu_name;
      }
    }
    $controller->save($menu_link);
  }
  if (!empty($children[$machine_name])) {
    foreach ($children[$machine_name] as $next_name) {
      _menu_link_save_recursive($controller, $next_name, $children, $links);
    }
  }
  // Remove processed link names so we can find stragglers.
  unset($children[$machine_name]);
}

/**
 * Gets all default menu link definitions.
 *
 * @return array
 *   An array of default menu links.
 */
function menu_link_get_defaults() {
  $module_handler = \Drupal::moduleHandler();
  $all_links = $module_handler->invokeAll('menu_link_defaults');
  // Fill in the machine name from the array key.
  foreach ($all_links as $machine_name => &$link) {
    $link['machine_name'] = $machine_name;
  }
  $module_handler->alter('menu_link_defaults', $all_links);
  return $all_links;
}

/**
 * Builds menu links for the items returned from hook_menu_link_defaults().
 */
function menu_link_rebuild_defaults() {
  // Ensure that all configuration used to build the menu items are loaded
  // without overrides.
  $old_state = \Drupal::configFactory()->getOverrideState();
  \Drupal::configFactory()->setOverrideState(FALSE);
  $module_handler = \Drupal::moduleHandler();
  if (!$module_handler->moduleExists('menu_link')) {
    // The Menu link module may not be available during install, so rebuild
    // when possible.
    return;
  }
  /** @var \Drupal\menu_link\MenuLinkStorageControllerInterface $menu_link_storage */
  $menu_link_storage = \Drupal::entityManager()
    ->getStorageController('menu_link');
  $links = array();
  $children = array();
  $top_links = array();
  $all_links = menu_link_get_defaults();
  if ($all_links) {
    foreach ($all_links as $machine_name => $link) {
      // For performance reasons, do a straight query now and convert to a menu
      // link entity later.
      // @todo revisit before release.
      $existing_item = db_select('menu_links')
        ->fields('menu_links')
        ->condition('machine_name', $machine_name)
        ->condition('module', 'system')
        ->execute()->fetchObject();
      if ($existing_item) {
        $existing_item->options = unserialize($existing_item->options);
        $existing_item->route_parameters = unserialize($existing_item->route_parameters);
        $link['mlid'] = $existing_item->mlid;
        $link['plid'] = $existing_item->plid;
        $link['uuid'] = $existing_item->uuid;
        $link['customized'] = $existing_item->customized;
        $link['updated'] = $existing_item->updated;
        $menu_link = $menu_link_storage->createFromDefaultLink($link);

        // Convert the existing item to a typed object.
        /** @var \Drupal\menu_link\MenuLinkInterface $existing_item */
        $existing_item = $menu_link_storage->create(get_object_vars($existing_item));

        if (!$existing_item->customized) {
          // A change in hook_menu_link_defaults() may move the link to a
          // different menu or parent.
          if (!empty($link['menu_name']) && ($link['menu_name'] != $existing_item->menu_name)) {
            $menu_link->plid = NULL;
            $menu_link->menu_name = $link['menu_name'];
          }
          elseif (!empty($link['parent'])) {
            $menu_link->plid = NULL;
          }

          $menu_link->original = $existing_item;
        }
      }
      else {
        if (empty($link['route_name']) && empty($link['link_path'])) {
          watchdog('error', 'Menu_link %machine_name does neither provide a route_name nor a link_path, so it got skipped.', array('%machine_name' => $machine_name));
          continue;
        }
        $menu_link = $menu_link_storage->createFromDefaultLink($link);
      }
      if (!empty($link['parent'])) {
        $children[$link['parent']][$machine_name] = $machine_name;
        $menu_link->parent = $link['parent'];
        if (empty($link['menu_name'])) {
          // Unset the default menu name so it's populated from the parent.
          unset($menu_link->menu_name);
        }
      }
      else {
        // A top level link - we need them to root our tree.
        $top_links[$machine_name] = $machine_name;
        $menu_link->plid = 0;
      }
      $links[$machine_name] = $menu_link;
    }
  }
  foreach ($top_links as $machine_name) {
    _menu_link_save_recursive($menu_link_storage, $machine_name, $children, $links);
  }
  // Handle any children we didn't find starting from top-level links.
  foreach ($children as $orphan_links) {
    foreach ($orphan_links as $machine_name) {
      // Force it to the top level.
      $links[$machine_name]->plid = 0;
      _menu_link_save_recursive($menu_link_storage, $machine_name, $children, $links);
    }
  }

  // Find any item whose entry in hook_menu_link_defaults() no longer exists.
  if ($all_links) {
    $query = \Drupal::entityQuery('menu_link')
      ->condition('machine_name', array_keys($all_links), 'NOT IN')
      ->exists('machine_name')
      ->condition('external', 0)
      ->condition('updated', 0)
      ->condition('customized', 0)
      ->sort('depth', 'DESC');
    $result = $query->execute();
  }
  else {
    $result = array();
  }

  // Remove all such items. Starting from those with the greatest depth will
  // minimize the amount of re-parenting done by the menu link controller.
  if ($result) {
    menu_link_delete_multiple($result, TRUE);
  }
  \Drupal::configFactory()->setOverrideState($old_state);
}

/**
 * Returns an array containing all links for a menu.
 *
 * @param $menu_name
 *   The name of the menu whose links should be returned.
 *
 * @return
 *   An array of menu links.
 */
function menu_load_links($menu_name) {
  $links = array();

  $query = \Drupal::entityQuery('menu_link')
    ->condition('menu_name', $menu_name)
    // Order by weight so as to be helpful for menus that are only one level
    // deep.
    ->sort('weight');
  $result = $query->execute();

  if (!empty($result)) {
    $links = menu_link_load_multiple($result);
  }

  return $links;
}

/**
 * Deletes all links for a menu.
 *
 * @param $menu_name
 *   The name of the menu whose links will be deleted.
 */
function menu_delete_links($menu_name) {
  $links = menu_load_links($menu_name);
  menu_link_delete_multiple(array_keys($links), FALSE, TRUE);
}

/**
 * Clears the page and block caches at most twice per page load.
 */
function _menu_clear_page_cache() {
  $cache_cleared = &drupal_static(__FUNCTION__, 0);

  // Clear the page and block caches, but at most twice, including at
  //  the end of the page load when there are multiple links saved or deleted.
  if ($cache_cleared == 0) {
    Cache::invalidateTags(array('content' => TRUE));
    // Keep track of which menus have expanded items.
    _menu_set_expanded_menus();
    $cache_cleared = 1;
  }
  elseif ($cache_cleared == 1) {
    drupal_register_shutdown_function('Drupal\Core\Cache\Cache::invalidateTags', array('content' => TRUE));
    // Keep track of which menus have expanded items.
    drupal_register_shutdown_function('_menu_set_expanded_menus');
    $cache_cleared = 2;
  }
}

/**
 * Updates a list of menus with expanded items.
 */
function _menu_set_expanded_menus() {
  $names = db_query("SELECT menu_name FROM {menu_links} WHERE expanded <> 0 GROUP BY menu_name")->fetchCol();
  \Drupal::state()->set('menu_expanded', $names);
}


/**
 * Checks whether the site is in maintenance mode.
 *
 * This function will log the current user out and redirect to front page
 * if the current user has no 'access site in maintenance mode' permission.
 *
 * @param $check_only
 *   If this is set to TRUE, the function will perform the access checks and
 *   return the site offline status, but not log the user out or display any
 *   messages.
 *
 * @return
 *   FALSE if the site is not in maintenance mode, the user login page is
 *   displayed, or the user has the 'access site in maintenance mode'
 *   permission. TRUE for anonymous users not being on the login page when the
 *   site is in maintenance mode.
 */
function _menu_site_is_offline($check_only = FALSE) {
  // Check if site is in maintenance mode.
  if (\Drupal::state()->get('system.maintenance_mode')) {
    if (user_access('access site in maintenance mode')) {
      // Ensure that the maintenance mode message is displayed only once
      // (allowing for page redirects) and specifically suppress its display on
      // the maintenance mode settings page.
      if (!$check_only && current_path() != 'admin/config/development/maintenance') {
        if (user_access('administer site configuration')) {
          drupal_set_message(t('Operating in maintenance mode. <a href="@url">Go online.</a>', array('@url' => url('admin/config/development/maintenance'))), 'status', FALSE);
        }
        else {
          drupal_set_message(t('Operating in maintenance mode.'), 'status', FALSE);
        }
      }
    }
    else {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * @} End of "defgroup menu".
 */
