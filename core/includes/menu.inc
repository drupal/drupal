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
 *     _controller: '\Drupal\dblog\Controller\DbLogController::overview'
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
 * entity.block.edit_form:
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
 * returns:
 * - A render array (see the
 *   @link theme_render Theme and render topic @endlink for more information),
 *   if _content is used in the routing file.
 *   This render array is then rendered in the requested format (HTML, dialog,
 *   modal, AJAX are supported by default). In the case of HTML, it will be
 *   surrounded by blocks by default: the Block module is enabled by default,
 *   and hence its Page Display Variant that surrounds the main content with
 *   blocks is also used by default.
 * - A \Symfony\Component\HttpFoundation\Response object, if _controller is
 *   used in the routing file.
 * As a note, if your module registers multiple simple routes, it is usual
 * (and usually easiest) to put all of their methods on one controller class.
 *
 * If the route has placeholders (see @ref sec_placeholders above) the
 * placeholders will be passed to the method (using reflection) by name.
 * For example, the placeholder '{myvar}' in a route will become the $myvar
 * parameter to the method.
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
 * module_name.links.menu.yml file (in the top-level directory for your module):
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
 *   administrative hierarchy. See system.links.menu.yml to find the main
 *   skeleton of the hierarchy.
 * - weight: Lower (negative) numbers come before higher (positive) numbers,
 *   for menu items with the same parent.
 *
 * Discovered menu links from other modules can be altered using
 * hook_menu_links_discovered_alter().
 *
 * @todo Derivatives will probably be defined for these; when they are, add
 *   documentation here.
 *
 * @section sec_tasks Defining groups of local tasks (tabs)
 * Local tasks appear as tabs on a page when there are at least two defined for
 * a route, including the base route as the main tab, and additional routes as
 * other tabs. Static local tasks can be defined by adding lines like the
 * following to a module_name.links.task.yml file (in the top-level directory
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
 * module_name.links.action.yml file (in the top-level directory for your
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
 * module_name.links.contextual.yml file (in the top-level directory for your
 * module):
 * @code
 * block_configure:
 *   title: 'Configure block'
 *   route_name: 'entity.block.edit_form'
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
 * @section Rendering menus
 * Once you have created menus (that contain menu links), you want to render
 * them. Drupal provides a block (Drupal\system\Plugin\Block\SystemMenuBlock) to
 * do so.
 *
 * However, perhaps you have more advanced needs and you're not satisfied with
 * what the menu blocks offer you. If that's the case, you'll want to:
 * - Instantiate \Drupal\Core\Menu\MenuTreeParameters, and set its values to
 *   match your needs. Alternatively, you can use
 *   MenuLinkTree::getCurrentRouteMenuTreeParameters() to get a typical
 *   default set of parameters, and then customize them to suit your needs.
 * - Call \Drupal\Core\MenuLinkTree::load() with your menu link tree parameters,
 *   this will return a menu link tree.
 * - Pass the menu tree to \Drupal\Core\Menu\MenuLinkTree::transform() to apply
 *   menu link tree manipulators that transform the tree. You will almost always
 *   want to apply access checking. The manipulators that you will typically
 *   need can be found in \Drupal\Core\Menu\DefaultMenuTreeManipulators.
 * - Potentially write a custom menu tree manipulator, see
 *   \Drupal\Core\Menu\DefaultMenuTreeManipulators for examples. This is only
 *   necessary if you want to do things like adding extra metadata to rendered
 *   links to display icons next to them.
 * - Pass the menu tree to \Drupal\Core\Menu\MenuLinkTree::build(), this will
 *   build a renderable array.
 *
 * Combined, that would look like this:
 * @code
 * $menu_tree = \Drupal::menuTree();
 * $menu_name = 'my_menu';
 *
 * // Build the typical default set of menu tree parameters.
 * $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
 *
 * // Load the tree based on this set of parameters.
 * $tree = $menu_tree->load($menu_name, $parameters);
 *
 * // Transform the tree using the manipulators you want.
 * $manipulators = array(
 *   // Only show links that are accessible for the current user.
 *   array('callable' => 'menu.default_tree_manipulators:checkAccess'),
 *   // Use the default sorting of menu links.
 *   array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
 * );
 * $tree = $menu_tree->transform($tree, $manipulators);
 *
 * // Finally, build a renderable array from the transformed tree.
 * $menu = $menu_tree->build($tree);
 *
 * $menu_html = drupal_render($menu);
 * @endcode
 */

/**
 * Prepares variables for single local task link templates.
 *
 * Default template: menu-local-task.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element containing:
 *     - #link: A menu link array with 'title', 'href', and 'localized_options'
 *       keys.
 *     - #active: A boolean indicating whether the local task is active.
 */
function template_preprocess_menu_local_task(&$variables) {
  $link = $variables['element']['#link'];
  $link += array(
    'localized_options' => array(),
  );
  $link_text = $link['title'];

  if (!empty($variables['element']['#active'])) {
    // Add text to indicate active tab for non-visual users.
    $active = '<span class="visually-hidden">' . t('(active tab)') . '</span>';
    $variables['attributes']['class'] = array('active');

    // If the link does not contain HTML already, String::checkPlain() it now.
    // After we set 'html'=TRUE the link will not be sanitized by l().
    if (empty($link['localized_options']['html'])) {
      $link['title'] = String::checkPlain($link['title']);
    }
    $link['localized_options']['html'] = TRUE;
    $link_text = t('!local-task-title!active', array('!local-task-title' => $link['title'], '!active' => $active));
  }
  $link['localized_options']['set_active_class'] = TRUE;

  $variables['link'] = array(
    '#type' => 'link',
    '#title' => $link_text,
    '#url' => $link['url'],
    '#options' => $link['localized_options'],
  );
}

/**
 * Prepares variables for single local action link templates.
 *
 * Default template: menu-local-action.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element containing:
 *     - #link: A menu link array with 'title', 'href', and 'localized_options'
 *       keys.
 */
function template_preprocess_menu_local_action(&$variables) {
  $link = $variables['element']['#link'];
  $link += array(
    'localized_options' => array(),
  );
  $link['localized_options']['attributes']['class'][] = 'button';
  $link['localized_options']['attributes']['class'][] = 'button-action';
  $link['localized_options']['set_active_class'] = TRUE;

  $variables['link'] = array(
    '#type' => 'link',
    '#title' => $link['title'],
    '#options' => $link['localized_options'],
    '#url' => $link['url'],
  );
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
    if (!\Drupal::request()->attributes->has('exception') && !empty($route_name)) {
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
 * Clears all cached menu data.
 *
 * This should be called any time broad changes
 * might have been made to the router items or menu links.
 */
function menu_cache_clear_all() {
  \Drupal::cache('menu')->invalidateAll();
}

/**
 * @} End of "defgroup menu".
 */
