<?php

/**
 * @file
 * Hooks and documentation related to the menu system, routing, and links.
 */

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
 * set up dynamic routes. The @link events Events topic @endlink is also
 * relevant to dynamic routes.
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
 *   @link theme_render Theme and render topic @endlink for more information).
 *   This render array is then rendered in the requested format (HTML, dialog,
 *   modal, AJAX are supported by default). In the case of HTML, it will be
 *   surrounded by blocks by default: the Block module is enabled by default,
 *   and hence its Page Display Variant that surrounds the main content with
 *   blocks is also used by default.
 * - A \Symfony\Component\HttpFoundation\Response object.
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
 *
 * @section sec_rendering Rendering menus
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
 *
 * @}
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alters all the menu links discovered by the menu link plugin manager.
 *
 * @param array $links
 *   The link definitions to be altered.
 *
 * @return array
 *   An array of discovered menu links. Each link has a key that is the machine
 *   name, which must be unique. By default, use the route name as the
 *   machine name. In cases where multiple links use the same route name, such
 *   as two links to the same page in different menus, or two links using the
 *   same route name but different route parameters, the suggested machine name
 *   patten is the route name followed by a dot and a unique suffix. For
 *   example, an additional logout link might have a machine name of
 *   user.logout.navigation, and default links provided to edit the article and
 *   page content types could use machine names
 *   entity.node_type.edit_form.article and entity.node_type.edit_form.page.
 *   Since the machine name may be arbitrary, you should never write code that
 *   assumes it is identical to the route name.
 *
 *   The value corresponding to each machine name key is an associative array
 *   that may contain the following key-value pairs:
 *   - title: (required) The untranslated title of the menu link.
 *   - description: The untranslated description of the link.
 *   - route_name: (optional) The route name to be used to build the path.
 *     Either a route_name or a url must be provided.
 *   - route_parameters: (optional) The route parameters to build the path.
 *   - url: (optional) If you have an external link use url instead
 *     of providing a route_name.
 *   - parent: (optional) The machine name of the link that is this link's menu
 *     parent.
 *   - weight: (optional) An integer that determines the relative position of
 *     items in the menu; higher-weighted items sink. Defaults to 0. Menu items
 *     with the same weight are ordered alphabetically.
 *   - menu_name: (optional) The machine name of a menu to put the link in, if
 *     not the default Tools menu.
 *   - expanded: (optional) If set to TRUE, and if a menu link is provided for
 *     this menu item (as a result of other properties), then the menu link is
 *     always expanded, equivalent to its 'always expanded' checkbox being set
 *     in the UI.
 *   - options: (optional) An array of options to be passed to _l() when
 *     generating a link from this menu item.
 *
 * @ingroup menu
 */
function hook_menu_links_discovered_alter(&$links) {
  // Change the weight and title of the user.logout link.
  $links['user.logout']['weight'] = -10;
  $links['user.logout']['title'] = 'Logout';
}

/**
 * Alter tabs and actions displayed on the page before they are rendered.
 *
 * This hook is invoked by menu_local_tasks(). The system-determined tabs and
 * actions are passed in by reference. Additional tabs or actions may be added.
 *
 * Each tab or action is an associative array containing:
 * - #theme: The theme function to use to render.
 * - #link: An associative array containing:
 *   - title: The localized title of the link.
 *   - href: The system path to link to.
 *   - localized_options: An array of options to pass to _l().
 * - #weight: The link's weight compared to other links.
 * - #active: Whether the link should be marked as 'active'.
 *
 * @param array $data
 *   An associative array containing:
 *   - actions: A list of of actions keyed by their href, each one being an
 *     associative array as described above.
 *   - tabs: A list of (up to 2) tab levels that contain a list of of tabs keyed
 *     by their href, each one being an associative array as described above.
 * @param string $route_name
 *   The route name of the page.
 *
 * @ingroup menu
 */
function hook_menu_local_tasks(&$data, $route_name) {
  // Add an action linking to node/add to all pages.
  $data['actions']['node/add'] = array(
      '#theme' => 'menu_local_action',
      '#link' => array(
          'title' => t('Add content'),
          'url' => Url::fromRoute('node.add_page'),
          'localized_options' => array(
              'attributes' => array(
                  'title' => t('Add content'),
              ),
          ),
      ),
  );

  // Add a tab linking to node/add to all pages.
  $data['tabs'][0]['node/add'] = array(
      '#theme' => 'menu_local_task',
      '#link' => array(
          'title' => t('Example tab'),
          'url' => Url::fromRoute('node.add_page'),
          'localized_options' => array(
              'attributes' => array(
                  'title' => t('Add content'),
              ),
          ),
      ),
  );
}

/**
 * Alter tabs and actions displayed on the page before they are rendered.
 *
 * This hook is invoked by menu_local_tasks(). The system-determined tabs and
 * actions are passed in by reference. Existing tabs or actions may be altered.
 *
 * @param array $data
 *   An associative array containing tabs and actions. See
 *   hook_menu_local_tasks() for details.
 * @param string $route_name
 *   The route name of the page.
 *
 * @see hook_menu_local_tasks()
 *
 * @ingroup menu
 */
function hook_menu_local_tasks_alter(&$data, $route_name) {
}

/**
 * Alter local actions plugins.
 *
 * @param array $local_actions
 *   The array of local action plugin definitions, keyed by plugin ID.
 *
 * @see \Drupal\Core\Menu\LocalActionInterface
 * @see \Drupal\Core\Menu\LocalActionManager
 *
 * @ingroup menu
 */
function hook_menu_local_actions_alter(&$local_actions) {
}

/**
 * Alter local tasks plugins.
 *
 * @param array $local_tasks
 *   The array of local tasks plugin definitions, keyed by plugin ID.
 *
 * @see \Drupal\Core\Menu\LocalTaskInterface
 * @see \Drupal\Core\Menu\LocalTaskManager
 *
 * @ingroup menu
 */
function hook_local_tasks_alter(&$local_tasks) {
  // Remove a specified local task plugin.
  unset($local_tasks['example_plugin_id']);
}

/**
 * Alter contextual links before they are rendered.
 *
 * This hook is invoked by
 * \Drupal\Core\Menu\ContextualLinkManager::getContextualLinkPluginsByGroup().
 * The system-determined contextual links are passed in by reference. Additional
 * links may be added and existing links can be altered.
 *
 * Each contextual link contains the following entries:
 * - title: The localized title of the link.
 * - route_name: The route name of the link.
 * - route_parameters: The route parameters of the link.
 * - localized_options: An array of URL options.
 * - (optional) weight: The weight of the link, which is used to sort the links.
 *
 *
 * @param array $links
 *   An associative array containing contextual links for the given $group,
 *   as described above. The array keys are used to build CSS class names for
 *   contextual links and must therefore be unique for each set of contextual
 *   links.
 * @param string $group
 *   The group of contextual links being rendered.
 * @param array $route_parameters.
 *   The route parameters passed to each route_name of the contextual links.
 *   For example:
 *   @code
 *   array('node' => $node->id())
 *   @endcode
 *
 * @see \Drupal\Core\Menu\ContextualLinkManager
 *
 * @ingroup menu
 */
function hook_contextual_links_alter(array &$links, $group, array $route_parameters) {
  if ($group == 'menu') {
    // Dynamically use the menu name for the title of the menu_edit contextual
    // link.
    $menu = \Drupal::entityManager()->getStorage('menu')->load($route_parameters['menu']);
    $links['menu_edit']['title'] = t('Edit menu: !label', array('!label' => $menu->label()));
  }
}

/**
 * Alter the plugin definition of contextual links.
 *
 * @param array $contextual_links
 *   An array of contextual_links plugin definitions, keyed by contextual link
 *   ID. Each entry contains the following keys:
 *     - title: The displayed title of the link
 *     - route_name: The route_name of the contextual link to be displayed
 *     - group: The group under which the contextual links should be added to.
 *       Possible values are e.g. 'node' or 'menu'.
 *
 * @see \Drupal\Core\Menu\ContextualLinkManager
 *
 * @ingroup menu
 */
function hook_contextual_links_plugins_alter(array &$contextual_links) {
  $contextual_links['menu_edit']['title'] = 'Edit the menu';
}

/**
 * Perform alterations to the breadcrumb built by the BreadcrumbManager.
 *
 * @param array $breadcrumb
 *   An array of breadcrumb link a tags, returned by the breadcrumb manager
 *   build method, for example
 *   @code
 *     array('<a href="/">Home</a>');
 *   @endcode
 * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
 *   The current route match.
 * @param array $context
 *   May include the following key:
 *   - builder: the instance of
 *     \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface that constructed this
 *     breadcrumb, or NULL if no builder acted based on the current attributes.
 *
 * @ingroup menu
 */
function hook_system_breadcrumb_alter(array &$breadcrumb, \Drupal\Core\Routing\RouteMatchInterface $route_match, array $context) {
  // Add an item to the end of the breadcrumb.
  $breadcrumb[] = Drupal::l(t('Text'), 'example_route_name');
}

/**
 * Alter the parameters for links.
 *
 * @param array $variables
 *   An associative array of variables defining a link. The link may be either a
 *   "route link" using \Drupal\Core\Utility\LinkGenerator::link(), which is
 *   exposed as the 'link_generator' service or a link generated by _l(). If the
 *   link is a "route link", 'route_name' will be set, otherwise 'path' will be
 *   set. The following keys can be altered:
 *   - text: The link text for the anchor tag as a translated string.
 *   - url_is_active: Whether or not the link points to the currently active
 *     URL.
 *   - url: The \Drupal\Core\Url object.
 *   - options: An associative array of additional options that will be passed
 *     to either \Drupal\Core\Routing\UrlGenerator::generateFromPath() or
 *     \Drupal\Core\Routing\UrlGenerator::generateFromRoute() to generate the
 *     href attribute for this link, and also used when generating the link.
 *     Defaults to an empty array. It may contain the following elements:
 *     - 'query': An array of query key/value-pairs (without any URL-encoding) to
 *       append to the URL.
 *     - absolute: Whether to force the output to be an absolute link (beginning
 *       with http:). Useful for links that will be displayed outside the site,
 *       such as in an RSS feed. Defaults to FALSE.
 *     - language: An optional language object. May affect the rendering of
 *       the anchor tag, such as by adding a language prefix to the path.
 *     - attributes: An associative array of HTML attributes to apply to the
 *       anchor tag. If element 'class' is included, it must be an array; 'title'
 *       must be a string; other elements are more flexible, as they just need
 *       to work as an argument for the constructor of the class
 *       Drupal\Core\Template\Attribute($options['attributes']).
 *     - html: Whether or not HTML should be allowed as the link text. If FALSE,
 *       the text will be run through
 *       \Drupal\Component\Utility\SafeMarkup::checkPlain() before being output.
 *
 * @see \Drupal\Core\Routing\UrlGenerator::generateFromPath()
 * @see \Drupal\Core\Routing\UrlGenerator::generateFromRoute()
 */
function hook_link_alter(&$variables) {
  // Add a warning to the end of route links to the admin section.
  if (isset($variables['route_name']) && strpos($variables['route_name'], 'admin') !== FALSE) {
    $variables['text'] .= ' (Warning!)';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
