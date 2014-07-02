<?php

/**
 * @file
 * API for the Drupal menu system.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * @defgroup menu Menu and routing system
 * @{
 * Define the navigation menus, and route page requests to code based on URLs.
 *
 * @section sec_overview Overview and terminology
 * The Drupal routing system defines how Drupal responds to URL requests that
 * the web server passes on to Drupal. The routing system is based on the
 * @link http://symfony.com Symfony framework. @endlink The central idea is
 * that Drupal subsystems and modules can register routes (basically, URL
 * paths and context); they can also register to respond dynamically to
 * routes, for more flexibility. When Drupal receives a URL request, it will
 * attempt to match the request to a registered route, and query dynamic
 * responders. If a match is made, Drupal will then instantiate the required
 * classes, gather the data, format it, and send it back to the web browser.
 * Otherwise, Drupal will return a 404 or 403 response.
 *
 * The menu system uses routes; it is used for navigation menus, local tasks,
 * local actions, and contextual links:
 * - Navigation menus are hierarchies of menu links; links point to routes or
 *   URLs.
 * - Menu links and their hierarchies can be defined by Drupal subsystems
 *   and modules, or created in the user interface using the Menu UI module.
 * - Local tasks are groups of related routes. Local tasks are usually rendered
 *   as a group of tabs.
 * - Local actions are used for operations such as adding a new item on a page
 *   that lists items of some type. Local actions are usually rendered as
 *   buttons.
 * - Contextual links are actions that are related to sections of rendered
 *   output, and are usually rendered as a pop-up list of links. The
 *   Contextual Links module handles the gathering and rendering of contextual
 *   links.
 *
 * The following sections of this topic provide an overview of the routing and
 * menu APIs. For more detailed information, see
 * https://www.drupal.org/developing/api/8/routing and
 * https://www.drupal.org/developing/api/8/menu
 *
 * @section sec_register Registering simple routes
 * To register a route, add lines similar to this to a module_name.routing.yml
 * file in your top-level module directory:
 * @code
 * dblog.overview:
 *   path: '/admin/reports/dblog'
 *   defaults:
 *     _content: '\Drupal\dblog\Controller\DbLogController::overview'
 *     _title: 'Recent log messages'
 *   requirements:
 *     _permission: 'access site reports'
 * @endcode
 * Some notes:
 * - The first line is the machine name of the route. Typically, it is prefixed
 *   by the machine name of the module that defines the route, or the name of
 *   a subsystem.
 * - The 'path' line gives the URL path of the route (relative to the site's
 *   base URL).
 * - The 'defaults' section tells how to build the main content of the route,
 *   and can also give other information, such as the page title and additional
 *   arguments for the route controller method. There are several possibilities
 *   for how to build the main content, including:
 *   - _content: A callable, usually a method on a page controller class
 *     (see @ref sec_controller below for details).
 *   - _controller: A callable, usually a method on a page controller class
 *     (see @ref sec_controller below for details).
 *   - _form: A form controller class. See the
 *     @link form_api Form API topic @endlink for more information about
 *     form controllers.
 *   - _entity_form: A form for editing an entity. See the
 *     @link entity_api Entity API topic @endlink for more information.
 * - The 'requirements' section is used in Drupal to give access permission
 *   instructions (it has other uses in the Symfony framework). Most
 *   routes have a simple permission-based access scheme, as shown in this
 *   example. See the @link user_api Permission system topic @endlink for
 *   more information about permissions.
 *
 * See https://www.drupal.org/node/2092643 for more details about *.routing.yml
 * files, and https://www.drupal.org/node/2122201 for information on how to
 * set up dynamic routes.
 *
 * @section sec_placeholders Defining routes with placeholders
 * Some routes have placeholders in them, and these can also be defined in a
 * module_name.routing.yml file, as in this example from the Block module:
 * @code
 * block.admin_edit:
 *   path: '/admin/structure/block/manage/{block}'
 *   defaults:
 *     _entity_form: 'block.default'
 *     _title: 'Configure block'
 *   requirements:
 *     _entity_access: 'block.update'
 * @endcode
 * In the path, '{block}' is a placeholder - it will be replaced by the
 * ID of the block that is being configured by the entity system. See the
 * @link entity_api Entity API topic @endlink for more information.
 *
 * @section sec_controller Route controllers for simple routes
 * For simple routes, after you have defined the route in a *.routing.yml file
 * (see @ref sec_register above), the next step is to define a page controller
 * class and method. Page controller classes do not necessarily need to
 * implement any particular interface or extend any particular base class. The
 * only requirement is that the method specified in your *.routing.yml file
 * return one of the following, depending on whether you specified _content or
 * _controller in the routing file defaults section:
 * - A render array (see the
 *   @link theme_render Theme and render topic @endlink for more information),
 *   if _content is used in the routing file.
 * - A \Drupal\Core\Page\HtmlFragmentInterface object (fragment or page), if
 *   _content is used in the routing file.
 * - A \Symfony\Component\HttpFoundation\Response object, if _controller is
 *   used in the routing file.
 * As a note, if your module registers multiple simple routes, it is usual
 * (and usually easiest) to put all of their methods on one controller class.
 *
 * Most controllers will need to display some information stored in the Drupal
 * database, which will involve using one or more Drupal services (see the
 * @link container Services and container topic @endlink). In order to properly
 * inject services, a controller should implement
 * \Drupal\Core\DependencyInjection\ContainerInjectionInterface; simple
 * controllers can do this by extending the
 * \Drupal\Core\Controller\ControllerBase class. See
 * \Drupal\dblog\Controller\DbLogController for a straightforward example of
 * a controller class.
 *
 * @section sec_links Defining menu links for the administrative menu
 * Routes for administrative tasks can be added to the main Drupal
 * administrative menu hierarchy. To do this, add lines like the following to a
 * module_name.menu_links.yml file (in the top-level directory for your module):
 * @code
 * dblog.overview:
 *   title: 'Recent log messages'
 *   parent: system.admin_reports
 *   description: 'View events that have recently been logged.'
 *   route_name: dblog.overview
 *   weight: -1
 * @endcode
 * Some notes:
 * - The first line is the machine name for your menu link, which usually
 *   matches the machine name of the route (given in the 'route_name' line).
 * - parent: The machine name of the menu link that is the parent in the
 *   administrative hierarchy. See system.menu_links.yml to find the main
 *   skeleton of the hierarchy.
 * - weight: Lower (negative) numbers come before higher (positive) numbers,
 *   for menu items with the same parent.
 *
 * Menu items from other modules can be altered using
 * hook_menu_link_defaults_alter().
 *
 * @todo Derivatives will probably be defined for these; when they are, add
 *   documentation here.
 *
 * @section sec_tasks Defining groups of local tasks (tabs)
 * Local tasks appear as tabs on a page when there are at least two defined for
 * a route, including the base route as the main tab, and additional routes as
 * other tabs. Static local tasks can be defined by adding lines like the
 * following to a module_name.local_tasks.yml file (in the top-level directory
 * for your module):
 * @code
 * book.admin:
 *   route_name: book.admin
 *   title: 'List'
 *   base_route: book.admin
 * book.settings:
 *   route_name: book.settings
 *   title: 'Settings'
 *   base_route: book.admin
 *   weight: 100
 * @endcode
 * Some notes:
 * - The first line is the machine name for your local task, which usually
 *   matches the machine name of the route (given in the 'route_name' line).
 * - base_route: The machine name of the main task (tab) for the set of local
 *   tasks.
 * - weight: Lower (negative) numbers come before higher (positive) numbers,
 *   for tasks on the same base route. If there is a tab whose route
 *   matches the base route, that will be the default/first tab shown.
 *
 * Local tasks from other modules can be altered using
 * hook_menu_local_tasks_alter().
 *
 * @todo Derivatives are in flux for these; when they are more stable, add
 *   documentation here.
 *
 * @section sec_actions Defining local actions for routes
 * Local actions can be defined for operations related to a given route. For
 * instance, adding content is a common operation for the content management
 * page, so it should be a local action. Static local actions can be
 * defined by adding lines like the following to a
 * module_name.local_actions.yml file (in the top-level directory for your
 * module):
 * @code
 * node.add_page:
 *   route_name: node.add_page
 *   title: 'Add content'
 *   appears_on:
 *     - system.admin_content
 * @endcode
 * Some notes:
 * - The first line is the machine name for your local action, which usually
 *   matches the machine name of the route (given in the 'route_name' line).
 * - appears_on: Machine names of one or more routes that this local task
 *   should appear on.
 *
 * Local actions from other modules can be altered using
 * hook_menu_local_actions_alter().
 *
 * @todo Derivatives are in flux for these; when they are more stable, add
 *   documentation here.
 *
 * @section sec_contextual Defining contextual links
 * Contextual links are displayed by the Contextual Links module for user
 * interface elements whose render arrays have a '#contextual_links' element
 * defined. For example, a block render array might look like this, in part:
 * @code
 * array(
 *   '#contextual_links' => array(
 *     'block' => array(
 *       'route_parameters' => array('block' => $entity->id()),
 *     ),
 *   ),
 * @endcode
 * In this array, the outer key 'block' defines a "group" for contextual
 * links, and the inner array provides values for the route's placeholder
 * parameters (see @ref sec_placeholders above).
 *
 * To declare that a defined route should be a contextual link for a
 * contextual links group, put lines like the following in a
 * module_name.contextual_links.yml file (in the top-level directory for your
 * module):
 * @code
 * block_configure:
 *   title: 'Configure block'
 *   route_name: 'block.admin_edit'
 *   group: 'block'
 * @endcode
 * Some notes:
 * - The first line is the machine name for your contextual link, which usually
 *   matches the machine name of the route (given in the 'route_name' line).
 * - group: This needs to match the link group defined in the render array.
 *
 * Contextual links from other modules can be altered using
 * hook_contextual_links_alter().
 *
 * @todo Derivatives are in flux for these; when they are more stable, add
 *   documentation here.
 */

/**
 * @defgroup menu_tree_parameters Menu tree parameters
 * @{
 * Parameters for a menu tree.
 */

/**
 * The maximum depth of a menu links tree - matches the number of p columns.
 *
 * @todo Move this constant to MenuLinkStorage along with all the tree
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
  /** @var \Drupal\menu_link\MenuTreeInterface $menu_tree */
  $menu_tree = \Drupal::service('menu_link.tree');
  $tree = $menu_tree->buildPageData($menu_name, $level + 1);

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

    $route_name = \Drupal::routeMatch()->getRouteName();
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
  $route_name = Drupal::routeMatch()->getRouteName();
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
 * Clears all cached menu data.
 *
 * This should be called any time broad changes
 * might have been made to the router items or menu links.
 */
function menu_cache_clear_all() {
  \Drupal::cache('data')->deleteAll();
  menu_reset_static_cache();
}

/**
 * Resets the menu system static cache.
 */
function menu_reset_static_cache() {
  \Drupal::entityManager()
    ->getStorage('menu_link')->resetCache();
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
 * Builds menu links for the items returned from the menu_link.static service.
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
  /** @var \Drupal\menu_link\MenuLinkStorageInterface $menu_link_storage */
  $menu_link_storage = \Drupal::entityManager()
    ->getStorage('menu_link');
  $links = array();
  $children = array();
  $top_links = array();
  $all_links = \Drupal::service('menu_link.static')->getLinks();
  if ($all_links) {
    foreach ($all_links as $machine_name => $link) {
      // For performance reasons, do a straight query now and convert to a menu
      // link entity later.
      // @todo revisit before release.
      $existing_item = db_select('menu_links')
        ->fields('menu_links')
        ->condition('machine_name', $machine_name)
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
        // @todo Do not create a new entity in order to update it, see
        //   https://drupal.org/node/2241865
        $menu_link->setOriginalId($existing_item->mlid);

        // Convert the existing item to a typed object.
        /** @var \Drupal\menu_link\MenuLinkInterface $existing_item */
        $existing_item = $menu_link_storage->create(get_object_vars($existing_item));

        if (!$existing_item->customized) {
          // A change in the default menu links may move the link to a
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
          \Drupal::logger('menu_link')->error('Menu_link %machine_name does neither provide a route_name nor a link_path, so it got skipped.', array('%machine_name' => $machine_name));
          continue;
        }
        $menu_link = $menu_link_storage->createFromDefaultLink($link);
      }
      if (!empty($link['parent'])) {
        $children[$link['parent']][$machine_name] = $machine_name;
        $menu_link->parent = $link['parent'];
        if (empty($link['menu_name'])) {
          // Reset the default menu name so it is populated from the parent.
          $menu_link->menu_name = NULL;
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

  // Find any item whose default menu link no longer exists.
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
 * Updates the expanded menu item state at most twice per page load.
 */
function _menu_update_expanded_menus() {
  $expanded_menus_updated = &drupal_static(__FUNCTION__, 0);

  // Update the expanded menu item state, but at most twice, including at
  //  the end of the page load when there are multiple links saved or deleted.
  if ($expanded_menus_updated == 0) {
    // Keep track of which menus have expanded items.
    _menu_set_expanded_menus();
    $expanded_menus_updated = 1;
  }
  elseif ($expanded_menus_updated == 1) {
    // Keep track of which menus have expanded items.
    drupal_register_shutdown_function('_menu_set_expanded_menus');
    $expanded_menus_updated = 2;
  }
}

/**
 * Updates a list of menus with expanded items.
 */
function _menu_set_expanded_menus() {
  $names = array();
  $result = Drupal::entityQueryAggregate('menu_link')
    ->condition('expanded', 0, '<>')
    ->groupBy('menu_name')
    ->execute();

  // Flatten the resulting array.
  foreach($result as $k => $v) {
    $names[$k] = $v['menu_name'];
  }

  \Drupal::state()->set('menu_expanded', $names);
}

/**
 * @} End of "defgroup menu".
 */
