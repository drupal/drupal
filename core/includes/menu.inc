<?php

/**
 * @file
 * API for the Drupal menu system.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;

/**
 * @defgroup menu Menu and routing system
 * @{
 * Define the navigation menus, and route page requests to code based on URLs.
 *
 * The Drupal routing system defines how Drupal responds to URLs passed to the
 * browser. The menu system, which depends on the routing system, is used for
 * navigation. The Menu UI module allows menus to be created in the user interface
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
 * Once you have a route defined, you can use module.menu_links.yml to
 * define links for your module's paths in the main Navigation menu or other
 * menus.
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
  /** @var \Drupal\Core\Url $url */
  $url = $element['#url'];
  $url->setOption('set_active_class', TRUE);
  $output = \Drupal::linkGenerator()->generateFromUrl($element['#title'], $url);
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
  $config = \Drupal::config('menu_ui.settings');
  return \Drupal::moduleHandler()->moduleExists('menu_ui') ? $config->get($name) : $default;
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
  /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree */
  $menu_tree = \Drupal::service('menu.link_tree');
  $tree = $menu_tree->buildPageData($menu_name, $level + 1);

  // Go down the active trail until the right level is reached.
  while ($level-- > 0 && $tree) {
    // Loop through the current level's items until we find one that is in trail.
    while ($item = array_shift($tree)) {
      if ($item['in_active_trail']) {
        // If the item is in the active trail, we continue in the subtree.
        $tree = empty($item['below']) ? array() : $item['below'];
        break;
      }
    }
  }

  // Create a single level of links.
  $links = array();
  foreach ($tree as $item) {
    /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
    $link = $item['link'];

    if ($link->isHidden()) {
      continue;
    }

    $class = '';
    $url = $link->getUrlObject();
    $l = $url->getOptions();
    $l['title'] = $link->getTitle();
    if ($url->isExternal()) {
      $l['href'] = $url->getPath();
    }
    else {
      $l['route_name'] = $url->getRouteName();
      $l['route_parameters'] = $url->getRouteParameters();
    }
    if ($item['in_active_trail']) {
      $class = ' active-trail';
      $l['attributes']['class'][] = 'active-trail';
    }
    // Keyed with the unique ID to generate classes in links.html.twig.
    $links['menu-' . $link->getPluginId() . $class] = $l;
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
  return count(Element::getVisibleChildren($links['tabs'])) > 1 ? $links['tabs'] : '';
}

/**
 * Returns the rendered local tasks at the second level.
 */
function menu_secondary_local_tasks() {
  $links = menu_local_tasks(1);
  // Do not display single tabs.
  return count(Element::getVisibleChildren($links['tabs'])) > 1 ? $links['tabs'] : '';
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
 * Clears all cached menu data.
 *
 * This should be called any time broad changes
 * might have been made to the router items or menu links.
 */
function menu_cache_clear_all() {
  \Drupal::cache('data')->deleteAll();
}

/**
 * Builds menu links for the items discovered as plugins.
 */
function menu_link_rebuild_defaults() {
  $menu_manager = \Drupal::service('plugin.manager.menu.link');
  $menu_manager->rebuild();
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
    $cache_cleared = 1;
  }
  elseif ($cache_cleared == 1) {
    drupal_register_shutdown_function('Drupal\Core\Cache\Cache::invalidateTags', array('content' => TRUE));
    $cache_cleared = 2;
  }
}

/**
 * @} End of "defgroup menu".
 */
