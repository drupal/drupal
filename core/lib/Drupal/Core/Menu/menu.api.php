<?php

/**
 * @file
 * Hooks and documentation related to the menu system and links.
 */

/**
 * @defgroup menu Menu system
 * @{
 * Define the navigation menus, local actions and tasks, and contextual links.
 *
 * @section sec_overview Overview and terminology
 * The menu system uses routes; see the
 * @link routing Routing API topic @endlink for more information. It is used
 * for navigation menus, local tasks, local actions, and contextual links:
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
 * The following sections of this topic provide an overview of the menu API.
 * For more detailed information, see
 * https://www.drupal.org/developing/api/8/menu
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
 *   need can be found in \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators.
 * - Potentially write a custom menu tree manipulator, see
 *   \Drupal\Core\Menu\DefaultMenuLinkTreeManipulators for examples. This is
 *   only necessary if you want to do things like adding extra metadata to
 *   rendered links to display icons next to them.
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
 *   - title: (required) The title of the menu link. If this should be
 *     translated, create a \Drupal\Core\StringTranslation\TranslatableMarkup
 *     object.
 *   - description: The description of the link. If this should be
 *     translated, create a \Drupal\Core\StringTranslation\TranslatableMarkup
 *     object.
 *   - route_name: (optional) The route name to be used to build the path.
 *     Either the route_name or url element must be provided.
 *   - route_parameters: (optional) The route parameters to build the path.
 *   - url: (optional) If you have an external link use this element instead of
 *     providing route_name.
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
 *   - options: (optional) An array of options to be passed to
 *     \Drupal\Core\Utility\LinkGeneratorInterface::generate() when generating
 *     a link from this menu item.
 *
 * @ingroup menu
 */
function hook_menu_links_discovered_alter(&$links) {
  // Change the weight and title of the user.logout link.
  $links['user.logout']['weight'] = -10;
  $links['user.logout']['title'] = new \Drupal\Core\StringTranslation\TranslatableMarkup('Logout');
  // Conditionally add an additional link with a title that's not translated.
  if (\Drupal::moduleHandler()->moduleExists('search')) {
    $links['menu.api.search'] = [
      'title' => \Drupal::config('system.site')->get('name'),
      'route_name' => 'menu.api.search',
      'description' => new \Drupal\Core\StringTranslation\TranslatableMarkup('View popular search phrases for this site.'),
      'parent' => 'system.admin_reports',
    ];
  }
}

/**
 * Alter local tasks displayed on the page before they are rendered.
 *
 * This hook is invoked by \Drupal\Core\Menu\LocalTaskManager::getLocalTasks().
 * The system-determined tabs and actions are passed in by reference. Additional
 * tabs may be added.
 *
 * The local tasks are under the 'tabs' element and keyed by plugin ID.
 *
 * Each local task is an associative array containing:
 * - #theme: The theme function to use to render.
 * - #link: An associative array containing:
 *   - title: The localized title of the link.
 *   - url: a Url object.
 *   - localized_options: An array of options to pass to
 *     \Drupal\Core\Utility\LinkGeneratorInterface::generate().
 * - #weight: The link's weight compared to other links.
 * - #active: Whether the link should be marked as 'active'.
 *
 * @param array $data
 *   An associative array containing list of (up to 2) tab levels that contain a
 *   list of tabs keyed by their href, each one being an associative array
 *   as described above.
 * @param string $route_name
 *   The route name of the page.
 *
 * @ingroup menu
 */
function hook_menu_local_tasks_alter(&$data, $route_name) {

  // Add a tab linking to node/add to all pages.
  $data['tabs'][0]['node.add_page'] = [
      '#theme' => 'menu_local_task',
      '#link' => [
          'title' => t('Example tab'),
          'url' => Url::fromRoute('node.add_page'),
          'localized_options' => [
              'attributes' => [
                  'title' => t('Add content'),
              ],
          ],
      ],
  ];
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
 * @param array $route_parameters
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
    $links['menu_edit']['title'] = t('Edit menu: @label', ['@label' => $menu->label()]);
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
 * @param \Drupal\Core\Breadcrumb\Breadcrumb $breadcrumb
 *   A breadcrumb object returned by BreadcrumbBuilderInterface::build().
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
function hook_system_breadcrumb_alter(\Drupal\Core\Breadcrumb\Breadcrumb &$breadcrumb, \Drupal\Core\Routing\RouteMatchInterface $route_match, array $context) {
  // Add an item to the end of the breadcrumb.
  $breadcrumb->addLink(\Drupal\Core\Link::createFromRoute(t('Text'), 'example_route_name'));
}

/**
 * Alter the parameters for links.
 *
 * @param array $variables
 *   An associative array of variables defining a link. The link may be either a
 *   "route link" using \Drupal\Core\Utility\LinkGenerator::link(), which is
 *   exposed as the 'link_generator' service or a link generated by
 *   \Drupal\Core\Utility\LinkGeneratorInterface::generate(). If the link is a
 *   "route link", 'route_name' will be set; otherwise, 'path' will be set.
 *   The following keys can be altered:
 *   - text: The link text for the anchor tag. If the hook implementation
 *     changes this text it needs to preserve the safeness of the original text.
 *     Using t() or \Drupal\Component\Utility\SafeMarkup::format() with
 *     @placeholder is recommended as this will escape the original text if
 *     necessary. If the resulting text is not marked safe it will be escaped.
 *   - url_is_active: Whether or not the link points to the currently active
 *     URL.
 *   - url: The \Drupal\Core\Url object.
 *   - options: An associative array of additional options that will be passed
 *     to either \Drupal\Core\Utility\UnroutedUrlAssembler::assemble() or
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
 *
 * @see \Drupal\Core\Utility\UnroutedUrlAssembler::assemble()
 * @see \Drupal\Core\Routing\UrlGenerator::generateFromRoute()
 */
function hook_link_alter(&$variables) {
  // Add a warning to the end of route links to the admin section.
  if (isset($variables['route_name']) && strpos($variables['route_name'], 'admin') !== FALSE) {
    $variables['text'] = t('@text (Warning!)', ['@text' => $variables['text']]);
  }
}

/**
 * @} End of "addtogroup hooks".
 */
