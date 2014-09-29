<?php

/**
 * @file
 * Hooks provided by Drupal core and the System module.
 */

use Drupal\Component\Utility\String;
use Drupal\Core\Url;
use Drupal\Core\Utility\UpdateException;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Defines one or more hooks that are exposed by a module.
 *
 * Normally hooks do not need to be explicitly defined. However, by declaring a
 * hook explicitly, a module may define a "group" for it. Modules that implement
 * a hook may then place their implementation in either $module.module or in
 * $module.$group.inc. If the hook is located in $module.$group.inc, then that
 * file will be automatically loaded when needed.
 * In general, hooks that are rarely invoked and/or are very large should be
 * placed in a separate include file, while hooks that are very short or very
 * frequently called should be left in the main module file so that they are
 * always available.
 *
 * @return
 *   An associative array whose keys are hook names and whose values are an
 *   associative array containing:
 *   - group: A string defining the group to which the hook belongs. The module
 *     system will determine whether a file with the name $module.$group.inc
 *     exists, and automatically load it when required.
 *
 * See system_hook_info() for all hook groups defined by Drupal core.
 *
 * @see hook_hook_info_alter().
 */
function hook_hook_info() {
  $hooks['token_info'] = array(
    'group' => 'tokens',
  );
  $hooks['tokens'] = array(
    'group' => 'tokens',
  );
  return $hooks;
}

/**
 * Perform periodic actions.
 *
 * Modules that require some commands to be executed periodically can
 * implement hook_cron(). The engine will then call the hook whenever a cron
 * run happens, as defined by the administrator. Typical tasks managed by
 * hook_cron() are database maintenance, backups, recalculation of settings
 * or parameters, automated mailing, and retrieving remote data.
 *
 * Short-running or non-resource-intensive tasks can be executed directly in
 * the hook_cron() implementation.
 *
 * Long-running tasks and tasks that could time out, such as retrieving remote
 * data, sending email, and intensive file tasks, should use the queue API
 * instead of executing the tasks directly. To do this, first define one or
 * more queues via a \Drupal\Core\Annotation\QueueWorker plugin. Then, add items
 * that need to be processed to the defined queues.
 */
function hook_cron() {
  // Short-running operation example, not using a queue:
  // Delete all expired records since the last cron run.
  $expires = \Drupal::state()->get('mymodule.cron_last_run', REQUEST_TIME);
  db_delete('mymodule_table')
    ->condition('expires', $expires, '>=')
    ->execute();
  \Drupal::state()->set('mymodule.cron_last_run', REQUEST_TIME);

  // Long-running operation example, leveraging a queue:
  // Fetch feeds from other sites.
  $result = db_query('SELECT * FROM {aggregator_feed} WHERE checked + refresh < :time AND refresh <> :never', array(
    ':time' => REQUEST_TIME,
    ':never' => AGGREGATOR_CLEAR_NEVER,
  ));
  $queue = \Drupal::queue('aggregator_feeds');
  foreach ($result as $feed) {
    $queue->createItem($feed);
  }
}

/**
 * Alter available data types for typed data wrappers.
 *
 * @param array $data_types
 *   An array of data type information.
 *
 * @see hook_data_type_info()
 */
function hook_data_type_info_alter(&$data_types) {
  $data_types['email']['class'] = '\Drupal\mymodule\Type\Email';
}

/**
 * Alter cron queue information before cron runs.
 *
 * Called by \Drupal\Core\Cron to allow modules to alter cron queue settings
 * before any jobs are processesed.
 *
 * @param array $queues
 *   An array of cron queue information.
 *
 * @see \Drupal\Core\QueueWorker\QueueWorkerInterface
 * @see \Drupal\Core\Annotation\QueueWorker
 * @see \Drupal\Core\Cron
 */
function hook_queue_info_alter(&$queues) {
  // This site has many feeds so let's spend 90 seconds on each cron run
  // updating feeds instead of the default 60.
  $queues['aggregator_feeds']['cron']['time'] = 90;
}

/**
 * Allows modules to declare their own Form API element types and specify their
 * default values.
 *
 * This hook allows modules to declare their own form element types and to
 * specify their default values. The values returned by this hook will be
 * merged with the elements returned by form constructor implementations and so
 * can return defaults for any Form APIs keys in addition to those explicitly
 * documented by \Drupal\Core\Render\ElementInfoManagerInterface::getInfo().
 *
 * @return array
 *   An associative array with structure identical to that of the return value
 *   of \Drupal\Core\Render\ElementInfoManagerInterface::getInfo().
 *
 * @deprecated Use an annotated class instead, see
 *   \Drupal\Core\Render\Element\ElementInterface.
 *
 * @see hook_element_info_alter()
 */
function hook_element_info() {
  $types['filter_format'] = array(
    '#input' => TRUE,
  );
  return $types;
}

/**
 * Alter the element type information returned from modules.
 *
 * A module may implement this hook in order to alter the element type defaults
 * defined by a module.
 *
 * @param array $types
 *   An associative array with structure identical to that of the return value
 *   of \Drupal\Core\Render\ElementInfoManagerInterface::getInfo().
 *
 * @see hook_element_info()
 */
function hook_element_info_alter(array &$types) {
  // Decrease the default size of textfields.
  if (isset($types['textfield']['#size'])) {
    $types['textfield']['#size'] = 40;
  }
}

/**
 * Perform necessary alterations to the JavaScript before it is presented on
 * the page.
 *
 * @param $javascript
 *   An array of all JavaScript being presented on the page.
 *
 * @see _drupal_add_js()
 * @see drupal_get_js()
 * @see drupal_js_defaults()
 */
function hook_js_alter(&$javascript) {
  // Swap out jQuery to use an updated version of the library.
  $javascript['core/assets/vendor/jquery/jquery.js']['data'] = drupal_get_path('module', 'jquery_update') . '/jquery.js';
}

/**
 * Alters the JavaScript/CSS library registry.
 *
 * Allows certain, contributed modules to update libraries to newer versions
 * while ensuring backwards compatibility. In general, such manipulations should
 * only be done by designated modules, since most modules that integrate with a
 * certain library also depend on the API of a certain library version.
 *
 * @param $libraries
 *   The JavaScript/CSS libraries provided by $module. Keyed by internal library
 *   name and passed by reference.
 * @param $module
 *   The name of the module that registered the libraries.
 */
function hook_library_info_alter(&$libraries, $module) {
  // Update Farbtastic to version 2.0.
  if ($module == 'core' && isset($libraries['jquery.farbtastic'])) {
    // Verify existing version is older than the one we are updating to.
    if (version_compare($libraries['jquery.farbtastic']['version'], '2.0', '<')) {
      // Update the existing Farbtastic to version 2.0.
      $libraries['jquery.farbtastic']['version'] = '2.0';
      // To accurately replace library files, the order of files and the options
      // of each file have to be retained; e.g., like this:
      $old_path = 'assets/vendor/farbtastic';
      // Since the replaced library files are no longer located in a directory
      // relative to the original extension, specify an absolute path (relative
      // to DRUPAL_ROOT / base_path()) to the new location.
      $new_path = '/' . drupal_get_path('module', 'farbtastic_update') . '/js';
      $new_js = array();
      $replacements = array(
        $old_path . '/farbtastic.js' => $new_path . '/farbtastic-2.0.js',
      );
      foreach ($libraries['jquery.farbtastic']['js'] as $source => $options) {
        if (isset($replacements[$source])) {
          $new_js[$replacements[$source]] = $options;
        }
        else {
          $new_js[$source] = $options;
        }
      }
      $libraries['jquery.farbtastic']['js'] = $new_js;
    }
  }
}

/**
 * Alters a JavaScript/CSS library before it is attached.
 *
 * Allows modules and themes to dynamically attach further assets to a library
 * when it is added to the page; e.g., to add JavaScript settings.
 *
 * This hook is only invoked once per library and page.
 *
 * @param array $library
 *   The JavaScript/CSS library that is being added.
 * @param string $name
 *   The name of the library.
 *
 * @see _drupal_add_library()
 */
function hook_library_alter(array &$library, $name) {
  if ($name == 'core/jquery.ui.datepicker') {
    // Note: If the added assets do not depend on additional request-specific
    // data supplied here, consider to statically register it directly via
    // hook_library_info_alter() already.
    $library['dependencies'][] = 'locale/drupal.locale.datepicker';

    $language_interface = \Drupal::languageManager()->getCurrentLanguage();
    $settings['jquery']['ui']['datepicker'] = array(
      'isRTL' => $language_interface->direction == LanguageInterface::DIRECTION_RTL,
      'firstDay' => \Drupal::config('system.date')->get('first_day'),
    );
    $library['js'][] = array(
      'type' => 'setting',
      'data' => $settings,
    );
  }
}

/**
 * Alter CSS files before they are output on the page.
 *
 * @param $css
 *   An array of all CSS items (files and inline CSS) being requested on the page.
 *
 * @see _drupal_add_css()
 * @see drupal_get_css()
 */
function hook_css_alter(&$css) {
  // Remove defaults.css file.
  unset($css[drupal_get_path('module', 'system') . '/defaults.css']);
}

/**
 * Alter the Ajax command data that is sent to the client.
 *
 * @param \Drupal\Core\Ajax\CommandInterface[] $data
 *   An array of all the rendered commands that will be sent to the client.
 *
 * @see \Drupal\Core\Ajax\AjaxResponse::ajaxRender()
 */
function hook_ajax_render_alter(array &$data) {
  // Inject any new status messages into the content area.
  $status_messages = array('#theme' => 'status_messages');
  $command = new \Drupal\Core\Ajax\PrependCommand('#block-system-main .content', drupal_render($status_messages));
  $data[] = $command->render();
}

/**
 * Add elements to a page before it is rendered.
 *
 * Use this hook when you want to add elements at the page level. For your
 * additions to be printed, they have to be placed below a top level array key
 * of the $page array that has the name of a region of the active theme.
 *
 * By default, valid region keys are 'page_top', 'header', 'sidebar_first',
 * 'content', 'sidebar_second' and 'page_bottom'. To get a list of all regions
 * of the active theme, use system_region_list($theme). Note that $theme is a
 * global variable.
 *
 * If you want to alter the elements added by other modules or if your module
 * depends on the elements of other modules, use hook_page_alter() instead which
 * runs after this hook.
 *
 * @param $page
 *   Nested array of renderable elements that make up the page.
 *
 * @see hook_page_alter()
 * @see DefaultHtmlFragmentRenderer::render()
 */
function hook_page_build(&$page) {
  $path = drupal_get_path('module', 'foo');
  // Add JavaScript/CSS assets to all pages.
  // @see drupal_process_attached()
  $page['#attached']['js'][$path . '/foo.js'] = array('every_page' => TRUE);
  $page['#attached']['css'][$path . '/foo.base.css'] = array('every_page' => TRUE);
  $page['#attached']['css'][$path . '/foo.theme.css'] = array('every_page' => TRUE);

  // Add a special CSS file to a certain page only.
  if (drupal_is_front_page()) {
    $page['#attached']['css'][] = $path . '/foo.front.css';
  }

  // Append a standard disclaimer to the content region on a node detail page.
  if (\Drupal::request()->attributes->get('node')) {
    $page['content']['disclaimer'] = array(
      '#markup' => t('Acme, Inc. is not responsible for the contents of this sample code.'),
      '#weight' => 25,
    );
  }
}

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
 *     Either a route_name or a link_path must be provided.
 *   - route_parameters: (optional) The route parameters to build the path.
 *   - link_path: (optional) If you have an external link use link_path instead
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
 *   - options: (optional) An array of options to be passed to l() when
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
 *   - localized_options: An array of options to pass to l().
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
 */
function hook_menu_local_tasks(&$data, $route_name) {
  // Add an action linking to node/add to all pages.
  $data['actions']['node/add'] = array(
    '#theme' => 'menu_local_action',
    '#link' => array(
      'title' => t('Add content'),
      'href' => 'node/add',
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
      'href' => 'node/add',
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
 * - localized_options: An array of options to pass to url().
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
 */
function hook_contextual_links_plugins_alter(array &$contextual_links) {
  $contextual_links['menu_edit']['title'] = 'Edit the menu';
}

/**
 * Perform alterations before a page is rendered.
 *
 * Use this hook when you want to remove or alter elements at the page
 * level, or add elements at the page level that depend on an other module's
 * elements (this hook runs after hook_page_build().
 *
 * If you are making changes to entities such as forms, menus, or user
 * profiles, use those objects' native alter hooks instead (hook_form_alter(),
 * for example).
 *
 * The $page array contains top level elements for each block region:
 * @code
 *   $page['page_top']
 *   $page['header']
 *   $page['sidebar_first']
 *   $page['content']
 *   $page['sidebar_second']
 *   $page['page_bottom']
 * @endcode
 *
 * The 'content' element contains the main content of the current page, and its
 * structure will vary depending on what module is responsible for building the
 * page. Some legacy modules may not return structured content at all: their
 * pre-rendered markup will be located in $page['content']['main']['#markup'].
 *
 * Pages built by Drupal's core Node module use a standard structure:
 *
 * @code
 *   // Node body.
 *   $page['content']['system_main']['nodes'][$nid]['body']
 *   // Array of links attached to the node (add comments, read more).
 *   $page['content']['system_main']['nodes'][$nid]['links']
 *   // The node entity itself.
 *   $page['content']['system_main']['nodes'][$nid]['#node']
 *   // The results pager.
 *   $page['content']['system_main']['pager']
 * @endcode
 *
 * Blocks may be referenced by their module/delta pair within a region:
 * @code
 *   // The login block in the first sidebar region.
 *   $page['sidebar_first']['user_login']['#block'];
 * @endcode
 *
 * @param $page
 *   Nested array of renderable elements that make up the page.
 *
 * @see hook_page_build()
 * @see DefaultHtmlFragmentRenderer::render()
 */
function hook_page_alter(&$page) {
  // Add help text to the user login block.
  $page['sidebar_first']['user_login']['help'] = array(
    '#weight' => -10,
    '#markup' => t('To post comments or add content, you first have to log in.'),
  );
}

/**
 * Perform alterations before a form is rendered.
 *
 * One popular use of this hook is to add form elements to the node form. When
 * altering a node form, the node entity can be retrieved by invoking
 * $form_state->getFormObject()->getEntity().
 *
 * In addition to hook_form_alter(), which is called for all forms, there are
 * two more specific form hooks available. The first,
 * hook_form_BASE_FORM_ID_alter(), allows targeting of a form/forms via a base
 * form (if one exists). The second, hook_form_FORM_ID_alter(), can be used to
 * target a specific form directly.
 *
 * The call order is as follows: all existing form alter functions are called
 * for module A, then all for module B, etc., followed by all for any base
 * theme(s), and finally for the theme itself. The module order is determined
 * by system weight, then by module name.
 *
 * Within each module, form alter hooks are called in the following order:
 * first, hook_form_alter(); second, hook_form_BASE_FORM_ID_alter(); third,
 * hook_form_FORM_ID_alter(). So, for each module, the more general hooks are
 * called first followed by the more specific.
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   The current state of the form. The arguments that
 *   \Drupal::formBuilder()->getForm() was originally called with are available
 *   in the array $form_state->getBuildInfo()['args'].
 * @param $form_id
 *   String representing the name of the form itself. Typically this is the
 *   name of the function that generated the form.
 *
 * @see hook_form_BASE_FORM_ID_alter()
 * @see hook_form_FORM_ID_alter()
 * @see forms_api_reference.html
 */
function hook_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  if (isset($form['type']) && $form['type']['#value'] . '_node_settings' == $form_id) {
    $upload_enabled_types = \Drupal::config('mymodule.settings')->get('upload_enabled_types');
    $form['workflow']['upload_' . $form['type']['#value']] = array(
      '#type' => 'radios',
      '#title' => t('Attachments'),
      '#default_value' => in_array($form['type']['#value'], $upload_enabled_types) ? 1 : 0,
      '#options' => array(t('Disabled'), t('Enabled')),
    );
    // Add a custom submit handler to save the array of types back to the config file.
    $form['actions']['submit']['#submit'][] = 'mymodule_upload_enabled_types_submit';
  }
}

/**
 * Provide a form-specific alteration instead of the global hook_form_alter().
 *
 * Modules can implement hook_form_FORM_ID_alter() to modify a specific form,
 * rather than implementing hook_form_alter() and checking the form ID, or
 * using long switch statements to alter multiple forms.
 *
 * Form alter hooks are called in the following order: hook_form_alter(),
 * hook_form_BASE_FORM_ID_alter(), hook_form_FORM_ID_alter(). See
 * hook_form_alter() for more details.
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   The current state of the form. The arguments that
 *   \Drupal::formBuilder()->getForm() was originally called with are available
 *   in the array $form_state->getBuildInfo()['args'].
 * @param $form_id
 *   String representing the name of the form itself. Typically this is the
 *   name of the function that generated the form.
 *
 * @see hook_form_alter()
 * @see hook_form_BASE_FORM_ID_alter()
 * @see \Drupal\Core\Form\FormBuilderInterface::prepareForm()
 * @see forms_api_reference.html
 */
function hook_form_FORM_ID_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Modification for the form with the given form ID goes here. For example, if
  // FORM_ID is "user_register_form" this code would run only on the user
  // registration form.

  // Add a checkbox to registration form about agreeing to terms of use.
  $form['terms_of_use'] = array(
    '#type' => 'checkbox',
    '#title' => t("I agree with the website's terms and conditions."),
    '#required' => TRUE,
  );
}

/**
 * Provide a form-specific alteration for shared ('base') forms.
 *
 * By default, when \Drupal::formBuilder()->getForm() is called, Drupal looks
 * for a function with the same name as the form ID, and uses that function to
 * build the form. In contrast, base forms allow multiple form IDs to be mapped
 * to a single base (also called 'factory') form function.
 *
 * Modules can implement hook_form_BASE_FORM_ID_alter() to modify a specific
 * base form, rather than implementing hook_form_alter() and checking for
 * conditions that would identify the shared form constructor.
 *
 * To identify the base form ID for a particular form (or to determine whether
 * one exists) check the $form_state. The base form ID is stored under
 * $form_state->getBuildInfo()['base_form_id'].
 *
 * Form alter hooks are called in the following order: hook_form_alter(),
 * hook_form_BASE_FORM_ID_alter(), hook_form_FORM_ID_alter(). See
 * hook_form_alter() for more details.
 *
 * @param $form
 *   Nested array of form elements that comprise the form.
 * @param $form_state
 *   The current state of the form.
 * @param $form_id
 *   String representing the name of the form itself. Typically this is the
 *   name of the function that generated the form.
 *
 * @see hook_form_alter()
 * @see hook_form_FORM_ID_alter()
 * @see \Drupal\Core\Form\FormBuilderInterface::prepareForm()
 */
function hook_form_BASE_FORM_ID_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Modification for the form with the given BASE_FORM_ID goes here. For
  // example, if BASE_FORM_ID is "node_form", this code would run on every
  // node form, regardless of node type.

  // Add a checkbox to the node form about agreeing to terms of use.
  $form['terms_of_use'] = array(
    '#type' => 'checkbox',
    '#title' => t("I agree with the website's terms and conditions."),
    '#required' => TRUE,
  );
}

/**
 * Alter an email message created with the drupal_mail() function.
 *
 * hook_mail_alter() allows modification of email messages created and sent
 * with drupal_mail(). Usage examples include adding and/or changing message
 * text, message fields, and message headers.
 *
 * Email messages sent using functions other than drupal_mail() will not
 * invoke hook_mail_alter(). For example, a contributed module directly
 * calling the drupal_mail_system()->mail() or PHP mail() function
 * will not invoke this hook. All core modules use drupal_mail() for
 * messaging, it is best practice but not mandatory in contributed modules.
 *
 * @param $message
 *   An array containing the message data. Keys in this array include:
 *  - 'id':
 *     The drupal_mail() id of the message. Look at module source code or
 *     drupal_mail() for possible id values.
 *  - 'to':
 *     The address or addresses the message will be sent to. The
 *     formatting of this string must comply with RFC 2822.
 *  - 'from':
 *     The address the message will be marked as being from, which is
 *     either a custom address or the site-wide default email address.
 *  - 'subject':
 *     Subject of the email to be sent. This must not contain any newline
 *     characters, or the email may not be sent properly.
 *  - 'body':
 *     An array of strings containing the message text. The message body is
 *     created by concatenating the individual array strings into a single text
 *     string using "\n\n" as a separator.
 *  - 'headers':
 *     Associative array containing mail headers, such as From, Sender,
 *     MIME-Version, Content-Type, etc.
 *  - 'params':
 *     An array of optional parameters supplied by the caller of drupal_mail()
 *     that is used to build the message before hook_mail_alter() is invoked.
 *  - 'language':
 *     The language object used to build the message before hook_mail_alter()
 *     is invoked.
 *  - 'send':
 *     Set to FALSE to abort sending this email message.
 *
 * @see drupal_mail()
 */
function hook_mail_alter(&$message) {
  if ($message['id'] == 'modulename_messagekey') {
    if (!example_notifications_optin($message['to'], $message['id'])) {
      // If the recipient has opted to not receive such messages, cancel
      // sending.
      $message['send'] = FALSE;
      return;
    }
    $message['body'][] = "--\nMail sent out from " . \Drupal::config('system.site')->get('name');
  }
}

/**
 * Alter the registry of modules implementing a hook.
 *
 * This hook is invoked during \Drupal::moduleHandler()->getImplementations().
 * A module may implement this hook in order to reorder the implementing
 * modules, which are otherwise ordered by the module's system weight.
 *
 * Note that hooks invoked using \Drupal::moduleHandler->alter() can have
 * multiple variations(such as hook_form_alter() and hook_form_FORM_ID_alter()).
 * \Drupal::moduleHandler->alter() will call all such variants defined by a
 * single module in turn. For the purposes of hook_module_implements_alter(),
 * these variants are treated as a single hook. Thus, to ensure that your
 * implementation of hook_form_FORM_ID_alter() is called at the right time,
 * you will have to change the order of hook_form_alter() implementation in
 * hook_module_implements_alter().
 *
 * @param $implementations
 *   An array keyed by the module's name. The value of each item corresponds
 *   to a $group, which is usually FALSE, unless the implementation is in a
 *   file named $module.$group.inc.
 * @param $hook
 *   The name of the module hook being implemented.
 */
function hook_module_implements_alter(&$implementations, $hook) {
  if ($hook == 'rdf_mapping') {
    // Move my_module_rdf_mapping() to the end of the list.
    // \Drupal::moduleHandler()->getImplementations()
    // iterates through $implementations with a foreach loop which PHP iterates
    // in the order that the items were added, so to move an item to the end of
    // the array, we remove it and then add it.
    $group = $implementations['my_module'];
    unset($implementations['my_module']);
    $implementations['my_module'] = $group;
  }
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
 */
function hook_system_breadcrumb_alter(array &$breadcrumb, \Drupal\Core\Routing\RouteMatchInterface $route_match, array $context) {
  // Add an item to the end of the breadcrumb.
  $breadcrumb[] = Drupal::l(t('Text'), 'example_route_name');
}

/**
 * Alter the information parsed from module and theme .info.yml files
 *
 * This hook is invoked in _system_rebuild_module_data() and in
 * _system_rebuild_theme_data(). A module may implement this hook in order to
 * add to or alter the data generated by reading the .info.yml file with
 * \Drupal\Core\Extension\InfoParser.
 *
 * @param array $info
 *   The .info.yml file contents, passed by reference so that it can be altered.
 * @param \Drupal\Core\Extension\Extension $file
 *   Full information about the module or theme.
 * @param string $type
 *   Either 'module' or 'theme', depending on the type of .info.yml file that
 *   was passed.
 */
function hook_system_info_alter(array &$info, \Drupal\Core\Extension\Extension $file, $type) {
  // Only fill this in if the .info.yml file does not define a 'datestamp'.
  if (empty($info['datestamp'])) {
    $info['datestamp'] = $file->getMTime();
  }
}

/**
 * Provide online user help.
 *
 * By implementing hook_help(), a module can make documentation available to
 * the user for the module as a whole, or for specific pages. Help for
 * developers should usually be provided via function header comments in the
 * code, or in special API example files.
 *
 * The page-specific help information provided by this hook appears as a system
 * help block on that page. The module overview help information is displayed
 * by the Help module. It can be accessed from the page at admin/help or from
 * the Extend page.
 *
 * For detailed usage examples of:
 * - Module overview help, see content_translation_help(). Module overview
 *   help should follow
 *   @link https://drupal.org/node/632280 the standard help template. @endlink
 * - Page-specific help using only routes, see book_help().
 * - Page-specific help using routes and $request, see block_help().
 *
 * @param string $route_name
 *   For page-specific help, use the route name as identified in the
 *   module's routing.yml file. For module overview help, the route name
 *   will be in the form of "help.page.$modulename".
 * @param Drupal\Core\Routing\RouteMatchInterface $route_match
 *   The current route match. This can be used to generate different help
 *   output for different pages that share the same route.
 *
 * @return string
 *   A localized string containing the help text.
 */
function hook_help($route_name, \Drupal\Core\Routing\RouteMatchInterface $route_match) {
  switch ($route_name) {
    // Main module help for the block module.
    case 'help.page.block':
      return '<p>' . t('Blocks are boxes of content rendered into an area, or region, of a web page. The default theme Bartik, for example, implements the regions "Sidebar first", "Sidebar second", "Featured", "Content", "Header", "Footer", etc., and a block may appear in any one of these areas. The <a href="!blocks">blocks administration page</a> provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions.', array('!blocks' => \Drupal::url('block.admin_display'))) . '</p>';

    // Help for another path in the block module.
    case 'block.admin_display':
      return '<p>' . t('This page provides a drag-and-drop interface for assigning a block to a region, and for controlling the order of blocks within regions. Since not all themes implement the same regions, or display regions in the same way, blocks are positioned on a per-theme basis. Remember that your changes will not be saved until you click the <em>Save blocks</em> button at the bottom of the page.') . '</p>';
  }
}

/**
 * Register a module or theme's theme implementations.
 *
 * The implementations declared by this hook have several purposes:
 * - They can specify how a particular render array is to be rendered as HTML.
 *   This is usually the case if the theme function is assigned to the render
 *   array's #theme property.
 * - They can return HTML for default calls to _theme().
 * - They can return HTML for calls to _theme() for a theme suggestion.
 *
 * @param array $existing
 *   An array of existing implementations that may be used for override
 *   purposes. This is primarily useful for themes that may wish to examine
 *   existing implementations to extract data (such as arguments) so that
 *   it may properly register its own, higher priority implementations.
 * @param $type
 *   Whether a theme, module, etc. is being processed. This is primarily useful
 *   so that themes tell if they are the actual theme being called or a parent
 *   theme. May be one of:
 *   - 'module': A module is being checked for theme implementations.
 *   - 'base_theme_engine': A theme engine is being checked for a theme that is
 *     a parent of the actual theme being used.
 *   - 'theme_engine': A theme engine is being checked for the actual theme
 *     being used.
 *   - 'base_theme': A base theme is being checked for theme implementations.
 *   - 'theme': The actual theme in use is being checked.
 * @param $theme
 *   The actual name of theme, module, etc. that is being being processed.
 * @param $path
 *   The directory path of the theme or module, so that it doesn't need to be
 *   looked up.
 *
 * @return array
 *   An associative array of information about theme implementations. The keys
 *   on the outer array are known as "theme hooks". For simple theme
 *   implementations for regular calls to _theme(), the theme hook is the first
 *   argument. For theme suggestions, instead of the array key being the base
 *   theme hook, the key is a theme suggestion name with the format
 *   'base_hook_name__sub_hook_name'. For render elements, the key is the
 *   machine name of the render element. The array values are themselves arrays
 *   containing information about the theme hook and its implementation. Each
 *   information array must contain either a 'variables' element (for _theme()
 *   calls) or a 'render element' element (for render elements), but not both.
 *   The following elements may be part of each information array:
 *   - variables: Used for _theme() call items only: an array of variables,
 *     where the array keys are the names of the variables, and the array
 *     values are the default values if they are not passed into _theme().
 *     Template implementations receive each array key as a variable in the
 *     template file (so they must be legal PHP/Twig variable names). Function
 *     implementations are passed the variables in a single $variables function
 *     argument.
 *   - render element: Used for render element items only: the name of the
 *     renderable element or element tree to pass to the theme function. This
 *     name is used as the name of the variable that holds the renderable
 *     element or tree in preprocess and process functions.
 *   - file: The file the implementation resides in. This file will be included
 *     prior to the theme being rendered, to make sure that the function or
 *     preprocess function (as needed) is actually loaded; this makes it
 *     possible to split theme functions out into separate files quite easily.
 *   - path: Override the path of the file to be used. Ordinarily the module or
 *     theme path will be used, but if the file will not be in the default
 *     path, include it here. This path should be relative to the Drupal root
 *     directory.
 *   - template: If specified, this theme implementation is a template, and
 *     this is the template file without an extension. Do not put .html.twig on
 *     this file; that extension will be added automatically by the default
 *     rendering engine (which is Twig). If 'path' above is specified, the
 *     template should also be in this path.
 *   - function: If specified, this will be the function name to invoke for
 *     this implementation. If neither 'template' nor 'function' is specified,
 *     a default function name will be assumed. For example, if a module
 *     registers the 'node' theme hook, 'theme_node' will be assigned to its
 *     function. If the chameleon theme registers the node hook, it will be
 *     assigned 'chameleon_node' as its function.
 *   - base hook: Used for _theme() suggestions only: the base theme hook name.
 *     Instead of this suggestion's implementation being used directly, the base
 *     hook will be invoked with this implementation as its first suggestion.
 *     The base hook's files will be included and the base hook's preprocess
 *     functions will be called in place of any suggestion's preprocess
 *     functions. If an implementation of hook_theme_suggestions_HOOK() (where
 *     HOOK is the base hook) changes the suggestion order, a different
 *     suggestion may be used in place of this suggestion. If after
 *     hook_theme_suggestions_HOOK() this suggestion remains the first
 *     suggestion, then this suggestion's function or template will be used to
 *     generate the output for _theme().
 *   - pattern: A regular expression pattern to be used to allow this theme
 *     implementation to have a dynamic name. The convention is to use __ to
 *     differentiate the dynamic portion of the theme. For example, to allow
 *     forums to be themed individually, the pattern might be: 'forum__'. Then,
 *     when the forum is themed, call:
 *     @code
 *     _theme(array('forum__' . $tid, 'forum'), $forum)
 *     @endcode
 *   - preprocess functions: A list of functions used to preprocess this data.
 *     Ordinarily this won't be used; it's automatically filled in. By default,
 *     for a module this will be filled in as template_preprocess_HOOK. For
 *     a theme this will be filled in as twig_preprocess and
 *     twig_preprocess_HOOK as well as themename_preprocess and
 *     themename_preprocess_HOOK.
 *   - override preprocess functions: Set to TRUE when a theme does NOT want
 *     the standard preprocess functions to run. This can be used to give a
 *     theme FULL control over how variables are set. For example, if a theme
 *     wants total control over how certain variables in the page.html.twig are
 *     set, this can be set to true. Please keep in mind that when this is used
 *     by a theme, that theme becomes responsible for making sure necessary
 *     variables are set.
 *   - type: (automatically derived) Where the theme hook is defined:
 *     'module', 'theme_engine', or 'theme'.
 *   - theme path: (automatically derived) The directory path of the theme or
 *     module, so that it doesn't need to be looked up.
 *
 * @see hook_theme_registry_alter()
 */
function hook_theme($existing, $type, $theme, $path) {
  return array(
    'forum_display' => array(
      'variables' => array('forums' => NULL, 'topics' => NULL, 'parents' => NULL, 'tid' => NULL, 'sortby' => NULL, 'forum_per_page' => NULL),
    ),
    'forum_list' => array(
      'variables' => array('forums' => NULL, 'parents' => NULL, 'tid' => NULL),
    ),
    'forum_icon' => array(
      'variables' => array('new_posts' => NULL, 'num_posts' => 0, 'comment_mode' => 0, 'sticky' => 0),
    ),
    'status_report' => array(
      'render element' => 'requirements',
      'file' => 'system.admin.inc',
    ),
  );
}

/**
 * Alter the theme registry information returned from hook_theme().
 *
 * The theme registry stores information about all available theme hooks,
 * including which callback functions those hooks will call when triggered,
 * what template files are exposed by these hooks, and so on.
 *
 * Note that this hook is only executed as the theme cache is re-built.
 * Changes here will not be visible until the next cache clear.
 *
 * The $theme_registry array is keyed by theme hook name, and contains the
 * information returned from hook_theme(), as well as additional properties
 * added by \Drupal\Core\Theme\Registry::processExtension().
 *
 * For example:
 * @code
 * $theme_registry['user'] = array(
 *   'variables' => array(
 *     'account' => NULL,
 *   ),
 *   'template' => 'core/modules/user/user',
 *   'file' => 'core/modules/user/user.pages.inc',
 *   'type' => 'module',
 *   'theme path' => 'core/modules/user',
 *   'preprocess functions' => array(
 *     0 => 'template_preprocess',
 *     1 => 'template_preprocess_user_profile',
 *   ),
 * );
 * @endcode
 *
 * @param $theme_registry
 *   The entire cache of theme registry information, post-processing.
 *
 * @see hook_theme()
 * @see \Drupal\Core\Theme\Registry::processExtension()
 */
function hook_theme_registry_alter(&$theme_registry) {
  // Kill the next/previous forum topic navigation links.
  foreach ($theme_registry['forum_topic_navigation']['preprocess functions'] as $key => $value) {
    if ($value == 'template_preprocess_forum_topic_navigation') {
      unset($theme_registry['forum_topic_navigation']['preprocess functions'][$key]);
    }
  }
}

/**
 * Alter the default, hook-independent variables for all templates.
 *
 * Allows modules to provide additional default template variables or manipulate
 * existing. This hook is invoked from template_preprocess() after basic default
 * template variables have been set up and before the next template preprocess
 * function is invoked.
 *
 * Note that the default template variables are statically cached within a
 * request. When adding a template variable that depends on other context, it is
 * your responsibility to appropriately reset the static cache in
 * template_preprocess() when needed:
 * @code
 * drupal_static_reset('template_preprocess');
 * @endcode
 *
 * See user_template_preprocess_default_variables_alter() for an example.
 *
 * @param array $variables
 *   An associative array of default template variables, as set up by
 *   _template_preprocess_default_variables(). Passed by reference.
 *
 * @see template_preprocess()
 * @see _template_preprocess_default_variables()
 */
function hook_template_preprocess_default_variables_alter(&$variables) {
  $variables['is_admin'] = \Drupal::currentUser()->hasPermission('access administration pages');
}

/**
 * Prepare a message based on parameters; called from drupal_mail().
 *
 * Note that hook_mail(), unlike hook_mail_alter(), is only called on the
 * $module argument to drupal_mail(), not all modules.
 *
 * @param $key
 *   An identifier of the mail.
 * @param $message
 *   An array to be filled in. Elements in this array include:
 *   - id: An ID to identify the mail sent. Look at module source code
 *     or drupal_mail() for possible id values.
 *   - to: The address or addresses the message will be sent to. The
 *     formatting of this string must comply with RFC 2822.
 *   - subject: Subject of the email to be sent. This must not contain any
 *     newline characters, or the mail may not be sent properly. drupal_mail()
 *     sets this to an empty string when the hook is invoked.
 *   - body: An array of lines containing the message to be sent. Drupal will
 *     format the correct line endings for you. drupal_mail() sets this to an
 *     empty array when the hook is invoked.
 *   - from: The address the message will be marked as being from, which is
 *     set by drupal_mail() to either a custom address or the site-wide
 *     default email address when the hook is invoked.
 *   - headers: Associative array containing mail headers, such as From,
 *     Sender, MIME-Version, Content-Type, etc. drupal_mail() pre-fills
 *     several headers in this array.
 * @param $params
 *   An array of parameters supplied by the caller of drupal_mail().
 */
function hook_mail($key, &$message, $params) {
  $account = $params['account'];
  $context = $params['context'];
  $variables = array(
    '%site_name' => \Drupal::config('system.site')->get('name'),
    '%username' => user_format_name($account),
  );
  if ($context['hook'] == 'taxonomy') {
    $entity = $params['entity'];
    $vocabulary = entity_load('taxonomy_vocabulary', $entity->id());
    $variables += array(
      '%term_name' => $entity->name,
      '%term_description' => $entity->description,
      '%term_id' => $entity->id(),
      '%vocabulary_name' => $vocabulary->name,
      '%vocabulary_description' => $vocabulary->description,
      '%vocabulary_id' => $vocabulary->id(),
    );
  }

  // Node-based variable translation is only available if we have a node.
  if (isset($params['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $params['node'];
    $variables += array(
      '%uid' => $node->getOwnerId(),
      '%url' => $node->url('canonical', array('absolute' => TRUE)),
      '%node_type' => node_get_type_label($node),
      '%title' => $node->getTitle(),
      '%teaser' => $node->teaser,
      '%body' => $node->body,
    );
  }
  $subject = strtr($context['subject'], $variables);
  $body = strtr($context['message'], $variables);
  $message['subject'] .= str_replace(array("\r", "\n"), '', $subject);
  $message['body'][] = drupal_html_to_text($body);
}

/**
 * Flush all persistent and static caches.
 *
 * This hook asks your module to clear all of its static caches,
 * in order to ensure a clean environment for subsequently
 * invoked data rebuilds.
 *
 * Do NOT use this hook for rebuilding information. Only use it to flush custom
 * caches.
 *
 * Static caches using drupal_static() do not need to be reset manually.
 * However, all other static variables that do not use drupal_static() must be
 * manually reset.
 *
 * This hook is invoked by drupal_flush_all_caches(). It runs before module data
 * is updated and before hook_rebuild().
 *
 * @see drupal_flush_all_caches()
 * @see hook_rebuild()
 */
function hook_cache_flush() {
  if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE == 'update') {
    _update_cache_clear();
  }
}

/**
 * Rebuild data based upon refreshed caches.
 *
 * This hook allows your module to rebuild its data based on the latest/current
 * module data. It runs after hook_cache_flush() and after all module data has
 * been updated.
 *
 * This hook is only invoked after the system has been completely cleared;
 * i.e., all previously cached data is known to be gone and every API in the
 * system is known to return current information, so your module can safely rely
 * on all available data to rebuild its own.
 *
 * @see hook_cache_flush()
 * @see drupal_flush_all_caches()
 */
function hook_rebuild() {
  $themes = list_themes();
  foreach ($themes as $theme) {
    _block_rehash($theme->getName());
  }
}

/**
 * Perform necessary actions before a module is installed.
 *
 * @param string $module
 *   The name of the module about to be installed.
 */
function hook_module_preinstall($module) {
  mymodule_cache_clear();
}

/**
 * Perform necessary actions after modules are installed.
 *
 * This function differs from hook_install() in that it gives all other modules
 * a chance to perform actions when a module is installed, whereas
 * hook_install() is only called on the module actually being installed. See
 * \Drupal\Core\Extension\ModuleHandler::install() for a detailed description of
 * the order in which install hooks are invoked.
 *
 * @param $modules
 *   An array of the modules that were installed.
 *
 * @see \Drupal\Core\Extension\ModuleHandler::install()
 * @see hook_install()
 */
function hook_modules_installed($modules) {
  if (in_array('lousy_module', $modules)) {
    \Drupal::state()->set('mymodule.lousy_module_compatibility', TRUE);
  }
}

/**
 * Perform necessary actions before a module is uninstalled.
 *
 * @param string $module
 *   The name of the module about to be uninstalled.
 */
function hook_module_preuninstall($module) {
  mymodule_cache_clear();
}

/**
 * Perform necessary actions when themes are installed.
 *
 * @param array $themes
 *   An array of theme names which are installed.
 */
function hook_themes_installed(array $themes) {
  // Add some state entries depending on the theme.
  foreach ($themes as $theme) {
    \Drupal::state()->set('example.' . $theme, 'some-value');
  }
}

/**
 * Perform necessary actions when themes are uninstalled.
 */
function hook_themes_uninstalled(array $themes) {
  // Remove some state entries depending on the theme.
  foreach ($themes as $theme) {
    \Drupal::state()->delete('example.' . $theme);
  }
}

/**
 * Perform necessary actions after modules are uninstalled.
 *
 * This function differs from hook_uninstall() in that it gives all other
 * modules a chance to perform actions when a module is uninstalled, whereas
 * hook_uninstall() is only called on the module actually being uninstalled.
 *
 * It is recommended that you implement this hook if your module stores
 * data that may have been set by other modules.
 *
 * @param $modules
 *   An array of the modules that were uninstalled.
 *
 * @see hook_uninstall()
 * @see hook_modules_disabled()
 */
function hook_modules_uninstalled($modules) {
  if (in_array('lousy_module', $modules)) {
    \Drupal::state()->delete('mymodule.lousy_module_compatibility');
  }
  mymodule_cache_rebuild();
}

/**
 * Registers PHP stream wrapper implementations associated with a module.
 *
 * Provide a facility for managing and querying user-defined stream wrappers
 * in PHP. PHP's internal stream_get_wrappers() doesn't return the class
 * registered to handle a stream, which we need to be able to find the handler
 * for class instantiation.
 *
 * If a module registers a scheme that is already registered with PHP, it will
 * be unregistered and replaced with the specified class.
 *
 * @return
 *   A nested array, keyed first by scheme name ("public" for "public://"),
 *   then keyed by the following values:
 *   - 'name' A short string to name the wrapper.
 *   - 'class' A string specifying the PHP class that implements the
 *     Drupal\Core\StreamWrapper\StreamWrapperInterface interface.
 *   - 'description' A string with a short description of what the wrapper does.
 *   - 'type' (Optional) A bitmask of flags indicating what type of streams this
 *     wrapper will access - local or remote, readable and/or writeable, etc.
 *     Many shortcut constants are defined in file.inc. Defaults to
 *     STREAM_WRAPPERS_NORMAL which includes all of these bit flags:
 *     - STREAM_WRAPPERS_READ
 *     - STREAM_WRAPPERS_WRITE
 *     - STREAM_WRAPPERS_VISIBLE
 *
 * @see file_get_stream_wrappers()
 * @see hook_stream_wrappers_alter()
 * @see system_stream_wrappers()
 */
function hook_stream_wrappers() {
  return array(
    'public' => array(
      'name' => t('Public files'),
      'class' => 'Drupal\Core\StreamWrapper\PublicStream',
      'description' => t('Public local files served by the webserver.'),
      'type' => STREAM_WRAPPERS_LOCAL_NORMAL,
    ),
    'private' => array(
      'name' => t('Private files'),
      'class' => 'Drupal\Core\StreamWrapper\PrivateStream',
      'description' => t('Private local files served by Drupal.'),
      'type' => STREAM_WRAPPERS_LOCAL_NORMAL,
    ),
    'temp' => array(
      'name' => t('Temporary files'),
      'class' => 'Drupal\Core\StreamWrapper\TemporaryStream',
      'description' => t('Temporary local files for upload and previews.'),
      'type' => STREAM_WRAPPERS_LOCAL_HIDDEN,
    ),
    'cdn' => array(
      'name' => t('Content delivery network files'),
      // @todo: Fix the name of this class when we decide on module PSR-0 usage.
      'class' => 'MyModuleCDNStream',
      'description' => t('Files served by a content delivery network.'),
      // 'type' can be omitted to use the default of STREAM_WRAPPERS_NORMAL
    ),
    'youtube' => array(
      'name' => t('YouTube video'),
      // @todo: Fix the name of this class when we decide on module PSR-0 usage.
      'class' => 'MyModuleYouTubeStream',
      'description' => t('Video streamed from YouTube.'),
      // A module implementing YouTube integration may decide to support using
      // the YouTube API for uploading video, but here, we assume that this
      // particular module only supports playing YouTube video.
      'type' => STREAM_WRAPPERS_READ_VISIBLE,
    ),
  );
}

/**
 * Alters the list of PHP stream wrapper implementations.
 *
 * @see file_get_stream_wrappers()
 * @see hook_stream_wrappers()
 */
function hook_stream_wrappers_alter(&$wrappers) {
  // Change the name of private files to reflect the performance.
  $wrappers['private']['name'] = t('Slow files');
}

/**
 * Control access to private file downloads and specify HTTP headers.
 *
 * This hook allows modules to enforce permissions on file downloads whenever
 * Drupal is handling file download, as opposed to the web server bypassing
 * Drupal and returning the file from a public directory. Modules can also
 * provide headers to specify information like the file's name or MIME type.
 *
 * @param $uri
 *   The URI of the file.
 * @return
 *   If the user does not have permission to access the file, return -1. If the
 *   user has permission, return an array with the appropriate headers. If the
 *   file is not controlled by the current module, the return value should be
 *   NULL.
 *
 * @see file_download()
 */
function hook_file_download($uri) {
  // Check to see if this is a config download.
  $scheme = file_uri_scheme($uri);
  $target = file_uri_target($uri);
  if ($scheme == 'temporary' && $target == 'config.tar.gz') {
    return array(
      'Content-disposition' => 'attachment; filename="config.tar.gz"',
    );
  }
}

/**
 * Alter the URL to a file.
 *
 * This hook is called from file_create_url(), and  is called fairly
 * frequently (10+ times per page), depending on how many files there are in a
 * given page.
 * If CSS and JS aggregation are disabled, this can become very frequently
 * (50+ times per page) so performance is critical.
 *
 * This function should alter the URI, if it wants to rewrite the file URL.
 *
 * @param $uri
 *   The URI to a file for which we need an external URL, or the path to a
 *   shipped file.
 */
function hook_file_url_alter(&$uri) {
  $user = \Drupal::currentUser();

  // User 1 will always see the local file in this example.
  if ($user->id() == 1) {
    return;
  }

  $cdn1 = 'http://cdn1.example.com';
  $cdn2 = 'http://cdn2.example.com';
  $cdn_extensions = array('css', 'js', 'gif', 'jpg', 'jpeg', 'png');

  // Most CDNs don't support private file transfers without a lot of hassle,
  // so don't support this in the common case.
  $schemes = array('public');

  $scheme = file_uri_scheme($uri);

  // Only serve shipped files and public created files from the CDN.
  if (!$scheme || in_array($scheme, $schemes)) {
    // Shipped files.
    if (!$scheme) {
      $path = $uri;
    }
    // Public created files.
    else {
      $wrapper = file_stream_wrapper_get_instance_by_scheme($scheme);
      $path = $wrapper->getDirectoryPath() . '/' . file_uri_target($uri);
    }

    // Clean up Windows paths.
    $path = str_replace('\\', '/', $path);

    // Serve files with one of the CDN extensions from CDN 1, all others from
    // CDN 2.
    $pathinfo = pathinfo($path);
    if (isset($pathinfo['extension']) && in_array($pathinfo['extension'], $cdn_extensions)) {
      $uri = $cdn1 . '/' . $path;
    }
    else {
      $uri = $cdn2 . '/' . $path;
    }
  }
}

/**
 * Check installation requirements and do status reporting.
 *
 * This hook has three closely related uses, determined by the $phase argument:
 * - Checking installation requirements ($phase == 'install').
 * - Checking update requirements ($phase == 'update').
 * - Status reporting ($phase == 'runtime').
 *
 * Note that this hook, like all others dealing with installation and updates,
 * must reside in a module_name.install file, or it will not properly abort
 * the installation of the module if a critical requirement is missing.
 *
 * During the 'install' phase, modules can for example assert that
 * library or server versions are available or sufficient.
 * Note that the installation of a module can happen during installation of
 * Drupal itself (by install.php) with an installation profile or later by hand.
 * As a consequence, install-time requirements must be checked without access
 * to the full Drupal API, because it is not available during install.php.
 * If a requirement has a severity of REQUIREMENT_ERROR, install.php will abort
 * or at least the module will not install.
 * Other severity levels have no effect on the installation.
 * Module dependencies do not belong to these installation requirements,
 * but should be defined in the module's .info.yml file.
 *
 * The 'runtime' phase is not limited to pure installation requirements
 * but can also be used for more general status information like maintenance
 * tasks and security issues.
 * The returned 'requirements' will be listed on the status report in the
 * administration section, with indication of the severity level.
 * Moreover, any requirement with a severity of REQUIREMENT_ERROR severity will
 * result in a notice on the administration configuration page.
 *
 * @param $phase
 *   The phase in which requirements are checked:
 *   - install: The module is being installed.
 *   - update: The module is enabled and update.php is run.
 *   - runtime: The runtime requirements are being checked and shown on the
 *     status report page.
 *
 * @return
 *   An associative array where the keys are arbitrary but must be unique (it
 *   is suggested to use the module short name as a prefix) and the values are
 *   themselves associative arrays with the following elements:
 *   - title: The name of the requirement.
 *   - value: The current value (e.g., version, time, level, etc). During
 *     install phase, this should only be used for version numbers, do not set
 *     it if not applicable.
 *   - description: The description of the requirement/status.
 *   - severity: The requirement's result/severity level, one of:
 *     - REQUIREMENT_INFO: For info only.
 *     - REQUIREMENT_OK: The requirement is satisfied.
 *     - REQUIREMENT_WARNING: The requirement failed with a warning.
 *     - REQUIREMENT_ERROR: The requirement failed with an error.
 */
function hook_requirements($phase) {
  $requirements = array();

  // Report Drupal version
  if ($phase == 'runtime') {
    $requirements['drupal'] = array(
      'title' => t('Drupal'),
      'value' => \Drupal::VERSION,
      'severity' => REQUIREMENT_INFO
    );
  }

  // Test PHP version
  $requirements['php'] = array(
    'title' => t('PHP'),
    'value' => ($phase == 'runtime') ? \Drupal::l(phpversion(), new Url('system.php')) : phpversion(),
  );
  if (version_compare(phpversion(), DRUPAL_MINIMUM_PHP) < 0) {
    $requirements['php']['description'] = t('Your PHP installation is too old. Drupal requires at least PHP %version.', array('%version' => DRUPAL_MINIMUM_PHP));
    $requirements['php']['severity'] = REQUIREMENT_ERROR;
  }

  // Report cron status
  if ($phase == 'runtime') {
    $cron_last = \Drupal::state()->get('system.cron_last');

    if (is_numeric($cron_last)) {
      $requirements['cron']['value'] = t('Last run !time ago', array('!time' => \Drupal::service('date.formatter')->formatInterval(REQUEST_TIME - $cron_last)));
    }
    else {
      $requirements['cron'] = array(
        'description' => t('Cron has not run. It appears cron jobs have not been setup on your system. Check the help pages for <a href="@url">configuring cron jobs</a>.', array('@url' => 'http://drupal.org/cron')),
        'severity' => REQUIREMENT_ERROR,
        'value' => t('Never run'),
      );
    }

    $requirements['cron']['description'] .= ' ' . t('You can <a href="@cron">run cron manually</a>.', array('@cron' => \Drupal::url('system.run_cron')));

    $requirements['cron']['title'] = t('Cron maintenance tasks');
  }

  return $requirements;
}

/**
 * Define the current version of the database schema.
 *
 * A Drupal schema definition is an array structure representing one or more
 * tables and their related keys and indexes. A schema is defined by
 * hook_schema() which must live in your module's .install file.
 *
 * The tables declared by this hook will be automatically created when the
 * module is installed, and removed when the module is uninstalled. This happens
 * before hook_install() is invoked, and after hook_uninstall() is invoked,
 * respectively.
 *
 * By declaring the tables used by your module via an implementation of
 * hook_schema(), these tables will be available on all supported database
 * engines. You don't have to deal with the different SQL dialects for table
 * creation and alteration of the supported database engines.
 *
 * See the Schema API Handbook at http://drupal.org/node/146843 for details on
 * schema definition structures.
 *
 * @return array
 *   A schema definition structure array. For each element of the
 *   array, the key is a table name and the value is a table structure
 *   definition.
 *
 * @see hook_schema_alter()
 *
 * @ingroup schemaapi
 */
function hook_schema() {
  $schema['node'] = array(
    // Example (partial) specification for table "node".
    'description' => 'The base table for nodes.',
    'fields' => array(
      'nid' => array(
        'description' => 'The primary identifier for a node.',
        'type' => 'serial',
        'unsigned' => TRUE,
        'not null' => TRUE,
      ),
      'vid' => array(
        'description' => 'The current {node_field_revision}.vid version identifier.',
        'type' => 'int',
        'unsigned' => TRUE,
        'not null' => TRUE,
        'default' => 0,
      ),
      'type' => array(
        'description' => 'The type of this node.',
        'type' => 'varchar',
        'length' => 32,
        'not null' => TRUE,
        'default' => '',
      ),
      'title' => array(
        'description' => 'The title of this node, always treated as non-markup plain text.',
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
        'default' => '',
      ),
    ),
    'indexes' => array(
      'node_changed'        => array('changed'),
      'node_created'        => array('created'),
    ),
    'unique keys' => array(
      'nid_vid' => array('nid', 'vid'),
      'vid'     => array('vid'),
    ),
    'foreign keys' => array(
      'node_revision' => array(
        'table' => 'node_field_revision',
        'columns' => array('vid' => 'vid'),
      ),
      'node_author' => array(
        'table' => 'users',
        'columns' => array('uid' => 'uid'),
      ),
    ),
    'primary key' => array('nid'),
  );
  return $schema;
}

/**
 * Perform alterations to existing database schemas.
 *
 * When a module modifies the database structure of another module (by
 * changing, adding or removing fields, keys or indexes), it should
 * implement hook_schema_alter() to update the default $schema to take its
 * changes into account.
 *
 * See hook_schema() for details on the schema definition structure.
 *
 * @param $schema
 *   Nested array describing the schemas for all modules.
 *
 * @ingroup schemaapi
 */
function hook_schema_alter(&$schema) {
  // Add field to existing schema.
  $schema['users']['fields']['timezone_id'] = array(
    'type' => 'int',
    'not null' => TRUE,
    'default' => 0,
    'description' => 'Per-user timezone configuration.',
  );
}

/**
 * Perform alterations to a structured query.
 *
 * Structured (aka dynamic) queries that have tags associated may be altered by any module
 * before the query is executed.
 *
 * @param $query
 *   A Query object describing the composite parts of a SQL query.
 *
 * @see hook_query_TAG_alter()
 * @see node_query_node_access_alter()
 * @see AlterableInterface
 * @see SelectInterface
 */
function hook_query_alter(Drupal\Core\Database\Query\AlterableInterface $query) {
  if ($query->hasTag('micro_limit')) {
    $query->range(0, 2);
  }
}

/**
 * Perform alterations to a structured query for a given tag.
 *
 * @param $query
 *   An Query object describing the composite parts of a SQL query.
 *
 * @see hook_query_alter()
 * @see node_query_node_access_alter()
 * @see AlterableInterface
 * @see SelectInterface
 */
function hook_query_TAG_alter(Drupal\Core\Database\Query\AlterableInterface $query) {
  // Skip the extra expensive alterations if site has no node access control modules.
  if (!node_access_view_all_nodes()) {
    // Prevent duplicates records.
    $query->distinct();
    // The recognized operations are 'view', 'update', 'delete'.
    if (!$op = $query->getMetaData('op')) {
      $op = 'view';
    }
    // Skip the extra joins and conditions for node admins.
    if (!\Drupal::currentUser()->hasPermission('bypass node access')) {
      // The node_access table has the access grants for any given node.
      $access_alias = $query->join('node_access', 'na', '%alias.nid = n.nid');
      $or = db_or();
      // If any grant exists for the specified user, then user has access to the node for the specified operation.
      foreach (node_access_grants($op, $query->getMetaData('account')) as $realm => $gids) {
        foreach ($gids as $gid) {
          $or->condition(db_and()
            ->condition($access_alias . '.gid', $gid)
            ->condition($access_alias . '.realm', $realm)
          );
        }
      }

      if (count($or->conditions())) {
        $query->condition($or);
      }

      $query->condition($access_alias . 'grant_' . $op, 1, '>=');
    }
  }
}

/**
 * Perform setup tasks when the module is installed.
 *
 * If the module implements hook_schema(), the database tables will
 * be created before this hook is fired.
 *
 * Implementations of this hook are by convention declared in the module's
 * .install file. The implementation can rely on the .module file being loaded.
 * The hook will only be called when a module is installed. The module's schema
 * version will be set to the module's greatest numbered update hook. Because of
 * this, any time a hook_update_N() is added to the module, this function needs
 * to be updated to reflect the current version of the database schema.
 *
 * See the @link http://drupal.org/node/146843 Schema API documentation @endlink
 * for details on hook_schema and how database tables are defined.
 *
 * Note that since this function is called from a full bootstrap, all functions
 * (including those in modules enabled by the current page request) are
 * available when this hook is called. Use cases could be displaying a user
 * message, or calling a module function necessary for initial setup, etc.
 *
 * Please be sure that anything added or modified in this function that can
 * be removed during uninstall should be removed with hook_uninstall().
 *
 * @see hook_schema()
 * @see \Drupal\Core\Extension\ModuleHandler::install()
 * @see hook_uninstall()
 * @see hook_modules_installed()
 */
function hook_install() {
  // Create the styles directory and ensure it's writable.
  $directory = file_default_scheme() . '://styles';
  $mode = isset($GLOBALS['install_state']['mode']) ? $GLOBALS['install_state']['mode'] : NULL;
  file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS, $mode);
}

/**
 * Perform a single update.
 *
 * For each change that requires one or more actions to be performed when
 * updating a site, add a new hook_update_N(), which will be called by
 * update.php. The documentation block preceding this function is stripped of
 * newlines and used as the description for the update on the pending updates
 * task list. Schema updates should adhere to the
 * @link http://drupal.org/node/150215 Schema API. @endlink
 *
 * Implementations of hook_update_N() are named (module name)_update_(number).
 * The numbers are composed of three parts:
 * - 1 digit for Drupal core compatibility.
 * - 1 digit for your module's major release version (e.g., is this the 8.x-1.*
 *   (1) or 8.x-2.* (2) series of your module).
 * - 2 digits for sequential counting, starting with 01.
 *
 * Examples:
 * - mymodule_update_8100(): This is the first update to get the database ready
 *   to run mymodule 8.x-1.*.
 * - mymodule_update_8200(): This is the first update to get the database ready
 *   to run mymodule 8.x-2.*.
 *
 * As of Drupal 8.0, the database upgrade system no longer supports updating a
 * database from an earlier major version of Drupal: update.php can be used to
 * upgrade from 7.x-1.x to 7.x-2.x, or 8.x-1.x to 8.x-2.x, but not from 7.x to
 * 8.x. Therefore, only update hooks numbered 8001 or later will run for
 * Drupal 8. 8000 is reserved for the minimum core schema version and defining
 * mymodule_update_8000() will result in an exception. Use the
 * @link https://drupal.org/node/2127611 Migration API @endlink instead to
 * migrate data from an earlier major version of Drupal.
 *
 * For further information about releases and release numbers see:
 * @link http://drupal.org/node/711070 Maintaining a drupal.org project with Git @endlink
 *
 * Never renumber update functions.
 *
 * Implementations of this hook should be placed in a mymodule.install file in
 * the same directory as mymodule.module. Drupal core's updates are implemented
 * using the system module as a name and stored in database/updates.inc.
 *
 * Not all module functions are available from within a hook_update_N() function.
 * In order to call a function from your mymodule.module or an include file,
 * you need to explicitly load that file first.
 *
 * During database updates the schema of any module could be out of date. For
 * this reason, caution is needed when using any API function within an update
 * function - particularly CRUD functions, functions that depend on the schema
 * (for example by using drupal_write_record()), and any functions that invoke
 * hooks.
 *
 * The $sandbox parameter should be used when a multipass update is needed, in
 * circumstances where running the whole update at once could cause PHP to
 * timeout. Each pass is run in a way that avoids PHP timeouts, provided each
 * pass remains under the timeout limit. To signify that an update requires
 * at least one more pass, set $sandbox['#finished'] to a number less than 1
 * (you need to do this each pass). The value of $sandbox['#finished'] will be
 * unset between passes but all other data in $sandbox will be preserved. The
 * system will stop iterating this update when $sandbox['#finished'] is left
 * unset or set to a number higher than 1. It is recommended that
 * $sandbox['#finished'] is initially set to 0, and then updated each pass to a
 * number between 0 and 1 that represents the overall % completed for this
 * update, finishing with 1.
 *
 * See the @link batch Batch operations topic @endlink for more information on
 * how to use the Batch API.
 *
 * @param array $sandbox
 *   Stores information for multipass updates. See above for more information.
 *
 * @throws \Drupal\Core\Utility\UpdateException|PDOException
 *   In case of error, update hooks should throw an instance of
 *   Drupal\Core\Utility\UpdateException with a meaningful message for the user.
 *   If a database query fails for whatever reason, it will throw a
 *   PDOException.
 *
 * @return string|null
 *   Optionally, update hooks may return a translated string that will be
 *   displayed to the user after the update has completed. If no message is
 *   returned, no message will be presented to the user.
 *
 * @see batch
 * @see schemaapi
 * @see hook_update_last_removed()
 * @see update_get_update_list()
 */
function hook_update_N(&$sandbox) {
  // For non-multipass updates, the signature can simply be;
  // function hook_update_N() {

  // For most updates, the following is sufficient.
  db_add_field('mytable1', 'newcol', array('type' => 'int', 'not null' => TRUE, 'description' => 'My new integer column.'));

  // However, for more complex operations that may take a long time,
  // you may hook into Batch API as in the following example.

  // Update 3 users at a time to have an exclamation point after their names.
  // (They're really happy that we can do batch API in this hook!)
  if (!isset($sandbox['progress'])) {
    $sandbox['progress'] = 0;
    $sandbox['current_uid'] = 0;
    // We'll -1 to disregard the uid 0...
    $sandbox['max'] = db_query('SELECT COUNT(DISTINCT uid) FROM {users}')->fetchField() - 1;
  }

  $users = db_select('users', 'u')
    ->fields('u', array('uid', 'name'))
    ->condition('uid', $sandbox['current_uid'], '>')
    ->range(0, 3)
    ->orderBy('uid', 'ASC')
    ->execute();

  foreach ($users as $user) {
    $user->setUsername($user->getUsername() . '!');
    db_update('users')
      ->fields(array('name' => $user->getUsername()))
      ->condition('uid', $user->id())
      ->execute();

    $sandbox['progress']++;
    $sandbox['current_uid'] = $user->id();
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

  if ($some_error_condition_met) {
    // In case of an error, simply throw an exception with an error message.
    throw new UpdateException('Something went wrong; here is what you should do.');
  }

  // To display a message to the user when the update is completed, return it.
  // If you do not want to display a completion message, simply return nothing.
  return t('The update did what it was supposed to do.');
}

/**
 * Return an array of information about module update dependencies.
 *
 * This can be used to indicate update functions from other modules that your
 * module's update functions depend on, or vice versa. It is used by the update
 * system to determine the appropriate order in which updates should be run, as
 * well as to search for missing dependencies.
 *
 * Implementations of this hook should be placed in a mymodule.install file in
 * the same directory as mymodule.module.
 *
 * @return
 *   A multidimensional array containing information about the module update
 *   dependencies. The first two levels of keys represent the module and update
 *   number (respectively) for which information is being returned, and the
 *   value is an array of information about that update's dependencies. Within
 *   this array, each key represents a module, and each value represents the
 *   number of an update function within that module. In the event that your
 *   update function depends on more than one update from a particular module,
 *   you should always list the highest numbered one here (since updates within
 *   a given module always run in numerical order).
 *
 * @see update_resolve_dependencies()
 * @see hook_update_N()
 */
function hook_update_dependencies() {
  // Indicate that the mymodule_update_8001() function provided by this module
  // must run after the another_module_update_8003() function provided by the
  // 'another_module' module.
  $dependencies['mymodule'][8001] = array(
    'another_module' => 8003,
  );
  // Indicate that the mymodule_update_8002() function provided by this module
  // must run before the yet_another_module_update_8005() function provided by
  // the 'yet_another_module' module. (Note that declaring dependencies in this
  // direction should be done only in rare situations, since it can lead to the
  // following problem: If a site has already run the yet_another_module
  // module's database updates before it updates its codebase to pick up the
  // newest mymodule code, then the dependency declared here will be ignored.)
  $dependencies['yet_another_module'][8005] = array(
    'mymodule' => 8002,
  );
  return $dependencies;
}

/**
 * Return a number which is no longer available as hook_update_N().
 *
 * If you remove some update functions from your mymodule.install file, you
 * should notify Drupal of those missing functions. This way, Drupal can
 * ensure that no update is accidentally skipped.
 *
 * Implementations of this hook should be placed in a mymodule.install file in
 * the same directory as mymodule.module.
 *
 * @return
 *   An integer, corresponding to hook_update_N() which has been removed from
 *   mymodule.install.
 *
 * @see hook_update_N()
 */
function hook_update_last_removed() {
  // We've removed the 8.x-1.x version of mymodule, including database updates.
  // The next update function is mymodule_update_8200().
  return 8103;
}

/**
 * Remove any information that the module sets.
 *
 * The information that the module should remove includes:
 * - state that the module has set using \Drupal::state()
 * - modifications to existing tables
 *
 * The module should not remove its entry from the module configuration.
 * Database tables defined by hook_schema() will be removed automatically.
 *
 * The uninstall hook must be implemented in the module's .install file. It
 * will fire when the module gets uninstalled but before the module's database
 * tables are removed, allowing your module to query its own tables during
 * this routine.
 *
 * @see hook_install()
 * @see hook_schema()
 * @see hook_disable()
 * @see hook_modules_uninstalled()
 */
function hook_uninstall() {
  // Remove the styles directory and generated images.
  file_unmanaged_delete_recursive(file_default_scheme() . '://styles');
}

/**
 * Return an array of tasks to be performed by an installation profile.
 *
 * Any tasks you define here will be run, in order, after the installer has
 * finished the site configuration step but before it has moved on to the
 * final import of languages and the end of the installation. You can have any
 * number of custom tasks to perform during this phase.
 *
 * Each task you define here corresponds to a callback function which you must
 * separately define and which is called when your task is run. This function
 * will receive the global installation state variable, $install_state, as
 * input, and has the opportunity to access or modify any of its settings. See
 * the install_state_defaults() function in the installer for the list of
 * $install_state settings used by Drupal core.
 *
 * At the end of your task function, you can indicate that you want the
 * installer to pause and display a page to the user by returning any themed
 * output that should be displayed on that page (but see below for tasks that
 * use the form API or batch API; the return values of these task functions are
 * handled differently). You should also use #title within the task
 * callback function to set a custom page title. For some tasks, however, you
 * may want to simply do some processing and pass control to the next task
 * without ending the page request; to indicate this, simply do not send back
 * a return value from your task function at all. This can be used, for
 * example, by installation profiles that need to configure certain site
 * settings in the database without obtaining any input from the user.
 *
 * The task function is treated specially if it defines a form or requires
 * batch processing; in that case, you should return either the form API
 * definition or batch API array, as appropriate. See below for more
 * information on the 'type' key that you must define in the task definition
 * to inform the installer that your task falls into one of those two
 * categories. It is important to use these APIs directly, since the installer
 * may be run non-interactively (for example, via a command line script), all
 * in one page request; in that case, the installer will automatically take
 * care of submitting forms and processing batches correctly for both types of
 * installations. You can inspect the $install_state['interactive'] boolean to
 * see whether or not the current installation is interactive, if you need
 * access to this information.
 *
 * Remember that a user installing Drupal interactively will be able to reload
 * an installation page multiple times, so you should use \Drupal::state() to
 * store any data that you may need later in the installation process. Any
 * temporary state must be removed using \Drupal::state()->delete() before
 * your last task has completed and control is handed back to the installer.
 *
 * @param array $install_state
 *   An array of information about the current installation state.
 *
 * @return array
 *   A keyed array of tasks the profile will perform during the final stage of
 *   the installation. Each key represents the name of a function (usually a
 *   function defined by this profile, although that is not strictly required)
 *   that is called when that task is run. The values are associative arrays
 *   containing the following key-value pairs (all of which are optional):
 *   - display_name: The human-readable name of the task. This will be
 *     displayed to the user while the installer is running, along with a list
 *     of other tasks that are being run. Leave this unset to prevent the task
 *     from appearing in the list.
 *   - display: This is a boolean which can be used to provide finer-grained
 *     control over whether or not the task will display. This is mostly useful
 *     for tasks that are intended to display only under certain conditions;
 *     for these tasks, you can set 'display_name' to the name that you want to
 *     display, but then use this boolean to hide the task only when certain
 *     conditions apply.
 *   - type: A string representing the type of task. This parameter has three
 *     possible values:
 *     - normal: (default) This indicates that the task will be treated as a
 *       regular callback function, which does its processing and optionally
 *       returns HTML output.
 *     - batch: This indicates that the task function will return a batch API
 *       definition suitable for batch_set(). The installer will then take care
 *       of automatically running the task via batch processing.
 *     - form: This indicates that the task function will return a standard
 *       form API definition (and separately define validation and submit
 *       handlers, as appropriate). The installer will then take care of
 *       automatically directing the user through the form submission process.
 *   - run: A constant representing the manner in which the task will be run.
 *     This parameter has three possible values:
 *     - INSTALL_TASK_RUN_IF_NOT_COMPLETED: (default) This indicates that the
 *       task will run once during the installation of the profile.
 *     - INSTALL_TASK_SKIP: This indicates that the task will not run during
 *       the current installation page request. It can be used to skip running
 *       an installation task when certain conditions are met, even though the
 *       task may still show on the list of installation tasks presented to the
 *       user.
 *     - INSTALL_TASK_RUN_IF_REACHED: This indicates that the task will run on
 *       each installation page request that reaches it. This is rarely
 *       necessary for an installation profile to use; it is primarily used by
 *       the Drupal installer for bootstrap-related tasks.
 *   - function: Normally this does not need to be set, but it can be used to
 *     force the installer to call a different function when the task is run
 *     (rather than the function whose name is given by the array key). This
 *     could be used, for example, to allow the same function to be called by
 *     two different tasks.
 *
 * @see install_state_defaults()
 * @see batch_set()
 */
function hook_install_tasks(&$install_state) {
  // Here, we define a variable to allow tasks to indicate that a particular,
  // processor-intensive batch process needs to be triggered later on in the
  // installation.
  $myprofile_needs_batch_processing = \Drupal::state()->get('myprofile.needs_batch_processing', FALSE);
  $tasks = array(
    // This is an example of a task that defines a form which the user who is
    // installing the site will be asked to fill out. To implement this task,
    // your profile would define a function named myprofile_data_import_form()
    // as a normal form API callback function, with associated validation and
    // submit handlers. In the submit handler, in addition to saving whatever
    // other data you have collected from the user, you might also call
    // \Drupal::state()->set('myprofile.needs_batch_processing', TRUE) if the
    // user has entered data which requires that batch processing will need to
    // occur later on.
    'myprofile_data_import_form' => array(
      'display_name' => t('Data import options'),
      'type' => 'form',
    ),
    // Similarly, to implement this task, your profile would define a function
    // named myprofile_settings_form() with associated validation and submit
    // handlers. This form might be used to collect and save additional
    // information from the user that your profile needs. There are no extra
    // steps required for your profile to act as an "installation wizard"; you
    // can simply define as many tasks of type 'form' as you wish to execute,
    // and the forms will be presented to the user, one after another.
    'myprofile_settings_form' => array(
      'display_name' => t('Additional options'),
      'type' => 'form',
    ),
    // This is an example of a task that performs batch operations. To
    // implement this task, your profile would define a function named
    // myprofile_batch_processing() which returns a batch API array definition
    // that the installer will use to execute your batch operations. Due to the
    // 'myprofile.needs_batch_processing' variable used here, this task will be
    // hidden and skipped unless your profile set it to TRUE in one of the
    // previous tasks.
    'myprofile_batch_processing' => array(
      'display_name' => t('Import additional data'),
      'display' => $myprofile_needs_batch_processing,
      'type' => 'batch',
      'run' => $myprofile_needs_batch_processing ? INSTALL_TASK_RUN_IF_NOT_COMPLETED : INSTALL_TASK_SKIP,
    ),
    // This is an example of a task that will not be displayed in the list that
    // the user sees. To implement this task, your profile would define a
    // function named myprofile_final_site_setup(), in which additional,
    // automated site setup operations would be performed. Since this is the
    // last task defined by your profile, you should also use this function to
    // call \Drupal::state()->delete('myprofile.needs_batch_processing') and
    // clean up the state that was used above. If you want the user to pass
    // to the final Drupal installation tasks uninterrupted, return no output
    // from this function. Otherwise, return themed output that the user will
    // see (for example, a confirmation page explaining that your profile's
    // tasks are complete, with a link to reload the current page and therefore
    // pass on to the final Drupal installation tasks when the user is ready to
    // do so).
    'myprofile_final_site_setup' => array(
    ),
  );
  return $tasks;
}

/**
 * Alter the full list of installation tasks.
 *
 * You can use this hook to change or replace any part of the Drupal
 * installation process that occurs after the installation profile is selected.
 *
 * @param $tasks
 *   An array of all available installation tasks, including those provided by
 *   Drupal core. You can modify this array to change or replace individual
 *   steps within the installation process.
 * @param $install_state
 *   An array of information about the current installation state.
 */
function hook_install_tasks_alter(&$tasks, $install_state) {
  // Replace the entire site configuration form provided by Drupal core
  // with a custom callback function defined by this installation profile.
  $tasks['install_configure_form']['function'] = 'myprofile_install_configure_form';
}

/**
 * Alter MIME type mappings used to determine MIME type from a file extension.
 *
 * Invoked by \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::guess(). It
 * is used to allow modules to add to or modify the default mapping from
 * \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::$defaultMapping.
 *
 * @param $mapping
 *   An array of mimetypes correlated to the extensions that relate to them.
 *   The array has 'mimetypes' and 'extensions' elements, each of which is an
 *   array.
 *
 * @see \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::guess()
 * @see \Drupal\Core\File\MimeType\ExtensionMimeTypeGuesser::$defaultMapping
 */
function hook_file_mimetype_mapping_alter(&$mapping) {
  // Add new MIME type 'drupal/info'.
  $mapping['mimetypes']['example_info'] = 'drupal/info';
  // Add new extension '.info.yml' and map it to the 'drupal/info' MIME type.
  $mapping['extensions']['info'] = 'example_info';
  // Override existing extension mapping for '.ogg' files.
  $mapping['extensions']['ogg'] = 189;
}

/**
 * Alter archiver information declared by other modules.
 *
 * See hook_archiver_info() for a description of archivers and the archiver
 * information structure.
 *
 * @param $info
 *   Archiver information to alter (return values from hook_archiver_info()).
 */
function hook_archiver_info_alter(&$info) {
  $info['tar']['extensions'][] = 'tgz';
}

/**
 * Alter the list of mail backend plugin definitions.
 *
 * @param array $info
 *   The mail backend plugin definitions to be altered.
 *
 * @see \Drupal\Core\Annotation\Mail
 * @see \Drupal\Core\Mail\MailManager
 */
function hook_mail_backend_info_alter(&$info) {
  unset($info['test_mail_collector']);
}

/**
 * Alters theme operation links.
 *
 * @param $theme_groups
 *   An associative array containing groups of themes.
 *
 * @see system_themes_page()
 */
function hook_system_themes_page_alter(&$theme_groups) {
  foreach ($theme_groups as $state => &$group) {
    foreach ($theme_groups[$state] as &$theme) {
      // Add a foo link to each list of theme operations.
      $theme->operations[] = array(
        'title' => t('Foo'),
        'href' => 'admin/appearance/foo',
        'query' => array('theme' => $theme->getName())
      );
    }
  }
}

/**
 * Provide replacement values for placeholder tokens.
 *
 * This hook is invoked when someone calls
 * \Drupal\Core\Utility\Token::replace(). That function first scans the text for
 * [type:token] patterns, and splits the needed tokens into groups by type.
 * Then hook_tokens() is invoked on each token-type group, allowing your module
 * to respond by providing replacement text for any of the tokens in the group
 * that your module knows how to process.
 *
 * A module implementing this hook should also implement hook_token_info() in
 * order to list its available tokens on editing screens.
 *
 * @param $type
 *   The machine-readable name of the type (group) of token being replaced, such
 *   as 'node', 'user', or another type defined by a hook_token_info()
 *   implementation.
 * @param $tokens
 *   An array of tokens to be replaced. The keys are the machine-readable token
 *   names, and the values are the raw [type:token] strings that appeared in the
 *   original text.
 * @param $data
 *   (optional) An associative array of data objects to be used when generating
 *   replacement values, as supplied in the $data parameter to
 *   \Drupal\Core\Utility\Token::replace().
 * @param $options
 *   (optional) An associative array of options for token replacement; see
 *   \Drupal\Core\Utility\Token::replace() for possible values.
 *
 * @return
 *   An associative array of replacement values, keyed by the raw [type:token]
 *   strings from the original text.
 *
 * @see hook_token_info()
 * @see hook_tokens_alter()
 */
function hook_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = language_load($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $sanitize = !empty($options['sanitize']);

  $replacements = array();

  if ($type == 'node' && !empty($data['node'])) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Simple key values on the node.
        case 'nid':
          $replacements[$original] = $node->nid;
          break;

        case 'title':
          $replacements[$original] = $sanitize ? String::checkPlain($node->getTitle()) : $node->getTitle();
          break;

        case 'edit-url':
          $replacements[$original] = $node->url('edit-form', $url_options);
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $node->getOwner() ? $node->getOwner() : user_load(0);
          $replacements[$original] = $sanitize ? String::checkPlain($account->label()) : $account->label();
          break;

        case 'created':
          $replacements[$original] = format_date($node->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, array('user' => $node->getOwner()), $options);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, array('date' => $node->getCreatedTime()), $options);
    }
  }

  return $replacements;
}

/**
 * Alter replacement values for placeholder tokens.
 *
 * @param $replacements
 *   An associative array of replacements returned by hook_tokens().
 * @param $context
 *   The context in which hook_tokens() was called. An associative array with
 *   the following keys, which have the same meaning as the corresponding
 *   parameters of hook_tokens():
 *   - 'type'
 *   - 'tokens'
 *   - 'data'
 *   - 'options'
 *
 * @see hook_tokens()
 */
function hook_tokens_alter(array &$replacements, array $context) {
  $options = $context['options'];

  if (isset($options['langcode'])) {
    $url_options['language'] = language_load($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  if ($context['type'] == 'node' && !empty($context['data']['node'])) {
    $node = $context['data']['node'];

    // Alter the [node:title] token, and replace it with the rendered content
    // of a field (field_title).
    if (isset($context['tokens']['title'])) {
      $title = $node->field_title->view('default');
      $replacements[$context['tokens']['title']] = drupal_render($title);
    }
  }
}

/**
 * Provide information about available placeholder tokens and token types.
 *
 * Tokens are placeholders that can be put into text by using the syntax
 * [type:token], where type is the machine-readable name of a token type, and
 * token is the machine-readable name of a token within this group. This hook
 * provides a list of types and tokens to be displayed on text editing screens,
 * so that people editing text can see what their token options are.
 *
 * The actual token replacement is done by
 * \Drupal\Core\Utility\Token::replace(), which invokes hook_tokens(). Your
 * module will need to implement that hook in order to generate token
 * replacements from the tokens defined here.
 *
 * @return
 *   An associative array of available tokens and token types. The outer array
 *   has two components:
 *   - types: An associative array of token types (groups). Each token type is
 *     an associative array with the following components:
 *     - name: The translated human-readable short name of the token type.
 *     - description (optional): A translated longer description of the token
 *       type.
 *     - needs-data: The type of data that must be provided to
 *       \Drupal\Core\Utility\Token::replace() in the $data argument (i.e., the
 *       key name in $data) in order for tokens of this type to be used in the
 *       $text being processed. For instance, if the token needs a node object,
 *       'needs-data' should be 'node', and to use this token in
 *       \Drupal\Core\Utility\Token::replace(), the caller needs to supply a
 *       node object as $data['node']. Some token data can also be supplied
 *       indirectly; for instance, a node object in $data supplies a user object
 *       (the author of the node), allowing user tokens to be used when only
 *       a node data object is supplied.
 *   - tokens: An associative array of tokens. The outer array is keyed by the
 *     group name (the same key as in the types array). Within each group of
 *     tokens, each token item is keyed by the machine name of the token, and
 *     each token item has the following components:
 *     - name: The translated human-readable short name of the token.
 *     - description (optional): A translated longer description of the token.
 *     - type (optional): A 'needs-data' data type supplied by this token, which
 *       should match a 'needs-data' value from another token type. For example,
 *       the node author token provides a user object, which can then be used
 *       for token replacement data in \Drupal\Core\Utility\Token::replace()
 *       without having to supply a separate user object.
 *
 * @see hook_token_info_alter()
 * @see hook_tokens()
 */
function hook_token_info() {
  $type = array(
    'name' => t('Nodes'),
    'description' => t('Tokens related to individual nodes.'),
    'needs-data' => 'node',
  );

  // Core tokens for nodes.
  $node['nid'] = array(
    'name' => t("Node ID"),
    'description' => t("The unique ID of the node."),
  );
  $node['title'] = array(
    'name' => t("Title"),
  );
  $node['edit-url'] = array(
    'name' => t("Edit URL"),
    'description' => t("The URL of the node's edit page."),
  );

  // Chained tokens for nodes.
  $node['created'] = array(
    'name' => t("Date created"),
    'type' => 'date',
  );
  $node['author'] = array(
    'name' => t("Author"),
    'type' => 'user',
  );

  return array(
    'types' => array('node' => $type),
    'tokens' => array('node' => $node),
  );
}

/**
 * Alter the metadata about available placeholder tokens and token types.
 *
 * @param $data
 *   The associative array of token definitions from hook_token_info().
 *
 * @see hook_token_info()
 */
function hook_token_info_alter(&$data) {
  // Modify description of node tokens for our site.
  $data['tokens']['node']['nid'] = array(
    'name' => t("Node ID"),
    'description' => t("The unique ID of the article."),
  );
  $data['tokens']['node']['title'] = array(
    'name' => t("Title"),
    'description' => t("The title of the article."),
  );

  // Chained tokens for nodes.
  $data['tokens']['node']['created'] = array(
    'name' => t("Date created"),
    'description' => t("The date the article was posted."),
    'type' => 'date',
  );
}

/**
 * Alter batch information before a batch is processed.
 *
 * Called by batch_process() to allow modules to alter a batch before it is
 * processed.
 *
 * @param $batch
 *   The associative array of batch information. See batch_set() for details on
 *   what this could contain.
 *
 * @see batch_set()
 * @see batch_process()
 *
 * @ingroup batch
 */
function hook_batch_alter(&$batch) {
}

/**
 * Provide information on Updaters (classes that can update Drupal).
 *
 * Drupal\Core\Updater\Updater is a class that knows how to update various parts
 * of the Drupal file system, for example to update modules that have newer
 * releases, or to install a new theme.
 *
 * @return
 *   An associative array of information about the updater(s) being provided.
 *   This array is keyed by a unique identifier for each updater, and the
 *   values are subarrays that can contain the following keys:
 *   - class: The name of the PHP class which implements this updater.
 *   - name: Human-readable name of this updater.
 *   - weight: Controls what order the Updater classes are consulted to decide
 *     which one should handle a given task. When an update task is being run,
 *     the system will loop through all the Updater classes defined in this
 *     registry in weight order and let each class respond to the task and
 *     decide if each Updater wants to handle the task. In general, this
 *     doesn't matter, but if you need to override an existing Updater, make
 *     sure your Updater has a lighter weight so that it comes first.
 *
 * @see drupal_get_updaters()
 * @see hook_updater_info_alter()
 */
function hook_updater_info() {
  return array(
    'module' => array(
      'class' => 'Drupal\Core\Updater\Module',
      'name' => t('Update modules'),
      'weight' => 0,
    ),
    'theme' => array(
      'class' => 'Drupal\Core\Updater\Theme',
      'name' => t('Update themes'),
      'weight' => 0,
    ),
  );
}

/**
 * Alter the Updater information array.
 *
 * An Updater is a class that knows how to update various parts of the Drupal
 * file system, for example to update modules that have newer releases, or to
 * install a new theme.
 *
 * @param array $updaters
 *   Associative array of updaters as defined through hook_updater_info().
 *   Alter this array directly.
 *
 * @see drupal_get_updaters()
 * @see hook_updater_info()
 */
function hook_updater_info_alter(&$updaters) {
  // Adjust weight so that the theme Updater gets a chance to handle a given
  // update task before module updaters.
  $updaters['theme']['weight'] = -1;
}

/**
 * Alter the default country list.
 *
 * @param $countries
 *   The associative array of countries keyed by two-letter country code.
 *
 * @see \Drupal\Core\Locale\CountryManager::getList().
 */
function hook_countries_alter(&$countries) {
  // Elbonia is now independent, so add it to the country list.
  $countries['EB'] = 'Elbonia';
}

/**
 * Register information about FileTransfer classes provided by a module.
 *
 * The FileTransfer class allows transferring files over a specific type of
 * connection. Core provides classes for FTP and SSH. Contributed modules are
 * free to extend the FileTransfer base class to add other connection types,
 * and if these classes are registered via hook_filetransfer_info(), those
 * connection types will be available to site administrators using the Update
 * manager when they are redirected to the authorize.php script to authorize
 * the file operations.
 *
 * @return array
 *   Nested array of information about FileTransfer classes. Each key is a
 *   FileTransfer type (not human readable, used for form elements and
 *   variable names, etc), and the values are subarrays that define properties
 *   of that type. The keys in each subarray are:
 *   - 'title': Required. The human-readable name of the connection type.
 *   - 'class': Required. The name of the FileTransfer class. The constructor
 *     will always be passed the full path to the root of the site that should
 *     be used to restrict where file transfer operations can occur (the $jail)
 *     and an array of settings values returned by the settings form.
 *   - 'file': Required. The include file containing the FileTransfer class.
 *     This should be a separate .inc file, not just the .module file, so that
 *     the minimum possible code is loaded when authorize.php is running.
 *   - 'file path': Optional. The directory (relative to the Drupal root)
 *     where the include file lives. If not defined, defaults to the base
 *     directory of the module implementing the hook.
 *   - 'weight': Optional. Integer weight used for sorting connection types on
 *     the authorize.php form.
 *
 * @see \Drupal\Core\FileTransfer\FileTransfer
 * @see authorize.php
 * @see hook_filetransfer_info_alter()
 * @see drupal_get_filetransfer_info()
 */
function hook_filetransfer_info() {
  $info['sftp'] = array(
    'title' => t('SFTP (Secure FTP)'),
    'class' => 'Drupal\Core\FileTransfer\SFTP',
    'weight' => 10,
  );
  return $info;
}

/**
 * Alter the FileTransfer class registry.
 *
 * @param array $filetransfer_info
 *   Reference to a nested array containing information about the FileTransfer
 *   class registry.
 *
 * @see hook_filetransfer_info()
 */
function hook_filetransfer_info_alter(&$filetransfer_info) {
  // Remove the FTP option entirely.
  unset($filetransfer_info['ftp']);
  // Make sure the SSH option is listed first.
  $filetransfer_info['ssh']['weight'] = -10;
}

/**
 * Alter the parameters for links.
 *
 * @param array $variables
 *   An associative array of variables defining a link. The link may be either a
 *   "route link" using \Drupal\Core\Utility\LinkGenerator::link(), which is
 *   exposed as the 'link_generator' service or a link generated by l(). If the
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
 *       \Drupal\Component\Utility\String::checkPlain() before being output.
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
 * Alter the configuration synchronization steps.
 *
 * @param array $sync_steps
 *   A one-dimensional array of \Drupal\Core\Config\ConfigImporter method names
 *   or callables that are invoked to complete the import, in the order that
 *   they will be processed. Each callable item defined in $sync_steps should
 *   either be a global function or a public static method. The callable should
 *   accept a $context array by reference. For example:
 *   <code>
 *     function _additional_configuration_step(&$context) {
 *       // Do stuff.
 *       // If finished set $context['finished'] = 1.
 *     }
 *   </code>
 *   For more information on creating batches, see the
 *   @link batch Batch operations @endlink documentation.
 *
 * @see callback_batch_operation()
 * @see \Drupal\Core\Config\ConfigImporter::initialize()
 */
function hook_config_import_steps_alter(&$sync_steps, \Drupal\Core\Config\ConfigImporter $config_importer) {
  $deletes = $config_importer->getUnprocessedConfiguration('delete');
  if (isset($deletes['field.storage.node.body'])) {
    $sync_steps[] = '_additional_configuration_step';
  }
}

/**
 * Alter config typed data definitions.
 *
 * For example you can alter the typed data types representing each
 * configuration schema type to change default labels or form element renderers
 * used for configuration translation.
 *
 * It is strongly advised not to use this hook to add new data types or to
 * change the structure of existing ones. Keep in mind that there are tools
 * that may use the configuration schema for static analysis of configuration
 * files, like the string extractor for the localization system. Such systems
 * won't work with dynamically defined configuration schemas.
 *
 * For adding new data types use configuration schema YAML files instead.
 *
 * @param $definitions
 *   Associative array of configuration type definitions keyed by schema type
 *   names. The elements are themselves array with information about the type.
 */
function hook_config_schema_info_alter(&$definitions) {
  // Enhance the text and date type definitions with classes to generate proper
  // form elements in ConfigTranslationFormBase. Other translatable types will
  // appear as a one line textfield.
  $definitions['text']['form_element_class'] = '\Drupal\config_translation\FormElement\Textarea';
  $definitions['date_format']['form_element_class'] = '\Drupal\config_translation\FormElement\DateFormat';
}

/**
 * @} End of "addtogroup hooks".
 */
