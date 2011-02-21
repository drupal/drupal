<?php
// $Id: page.admin.inc,v 1.30 2011/01/05 22:35:46 merlinofchaos Exp $

/**
 * @file
 * Administrative functions for the page subtasks.
 *
 * These are attached to the menu system in page.inc via the hook_menu
 * delegation. They are included here so that this code is loaded
 * only when needed.
 */

/**
 * Delegated implementation of hook_menu().
 */
function page_manager_page_menu(&$items, $task) {
  // Set up access permissions.
  $access_callback = isset($task['admin access callback']) ? $task['admin access callback'] : 'user_access';
  $access_arguments = isset($task['admin access arguments']) ? $task['admin access arguments'] : array('administer page manager');

  $base = array(
    'access callback' => $access_callback,
    'access arguments' => $access_arguments,
    'file' => 'plugins/tasks/page.admin.inc',
  );

  $items['admin/structure/pages/add'] = array(
    'title' => 'Add custom page',
    'page callback' => 'page_manager_page_add_subtask',
    'page arguments' => array(),
    'type' => MENU_LOCAL_ACTION,
  ) + $base;

  $items['admin/structure/pages/import'] = array(
    'title' => 'Import page',
    'page callback' => 'drupal_get_form',
    'page arguments' => array('page_manager_page_import_subtask', 'page'),
    'type' => MENU_LOCAL_ACTION,
  ) + $base;
  if ($access_callback == 'user_access') {
    $items['admin/structure/pages/import']['access callback'] = 'ctools_access_multiperm';
    $items['admin/structure/pages/import']['access arguments'][] = 'use PHP for settings';
  }

  // AJAX callbacks for argument modal.
  $items['admin/structure/pages/argument'] = array(
    'page callback' => 'page_manager_page_subtask_argument_ajax',
    'type' => MENU_CALLBACK,
  ) + $base;

  // Add menu entries for each subtask
  foreach (page_manager_page_load_all() as $subtask_id => $subtask) {
    if (!empty($subtask->disabled)) {
      continue;
    }

    if (!isset($subtask->access['type'])) {
      $subtask->access['type'] = 'none';
    }
    if (!isset($subtask->access['settings'])) {
      $subtask->access['settings'] = NULL;
    }

    $path             = array();
    $page_arguments   = array($subtask_id);
    $access_arguments = array($subtask->access);
    $load_arguments   = array($subtask_id, '%index', '%map');

    // Replace named placeholders with our own placeholder to load contexts.
    $position = 0;

    foreach (explode('/', $subtask->path) as $bit) {
      // Remove things like double slashes completely.
      if (!isset($bit) || $bit === '') {
        continue;
      }

      if ($bit[0] == '%' && $bit != '%') {
        $placeholder = '%pm_arg';

        // Chop off that %.
        $name = substr($bit, 1);

        // Check to see if the argument plugin wants to use a different
        // placholder. This will allow to_args.
        if (!empty($subtask->arguments[$name])) {
          ctools_include('context');
          if (!empty($subtask->arguments[$name]['name'])) {
            $plugin = ctools_get_argument($subtask->arguments[$name]['name']);
            if (isset($plugin['path placeholder'])) {
              if (function_exists($plugin['path placeholder'])) {
                $placeholder = $plugin['path placeholder']($subtask->arguments[$name]);
              }
              else {
                $placeholder = $plugin['path placeholder'];
              }
            }
          }
        }
        // If an argument, swap it out with our argument loader and make sure
        // the argument gets passed through to the page callback.
        $path[]             = $placeholder;
        $page_arguments[]   = $position;
        $access_arguments[] = $position;
      }
      else if ($bit[0] != '!') {
        $path[] = $bit;
      }

      // Increment position. We do it like this to skip empty items that
      // could happen from erroneous paths like: this///that
      $position++;
    }

    $menu_path = implode('/', $path);

    $items[$menu_path] = page_manager_page_menu_item($task, $subtask->menu, $access_arguments, $page_arguments, $load_arguments);

    // Add a parent menu item if one is configured.
    if (isset($subtask->menu['type']) && $subtask->menu['type'] == 'default tab' && $subtask->menu['parent']['type'] != 'none') {
      array_pop($path);
      $parent_path = implode('/', $path);
      $items[$parent_path] = page_manager_page_menu_item($task, $subtask->menu['parent'], $access_arguments, $page_arguments, $load_arguments);
    }
  }
}

/**
 * Create a menu item for page manager pages.
 *
 * @param $menu
 *   The configuration to use. It will contain a type, and depending on the
 *   type may also contain weight, title and name. These are presumed to have
 *   been configured from the UI.
 * @param $access_arguments
 *   Arguments that go with ctools_access_menu; it should be loaded with
 *   the access plugin type, settings, and positions of any arguments that
 *   may produce contexts.
 * @param $page_arguments
 *   This should be seeded with the subtask name for easy loading and like
 *   the access arguments above should contain positions of arguments so
 *   that the menu system passes contexts through.
 * @param $load_arguments
 *   Arguments to send to the arg loader; should be the subtask id and '%index'.
 */
function page_manager_page_menu_item($task, $menu, $access_arguments, $page_arguments, $load_arguments) {
  $item = array(
    'access callback' => 'ctools_access_menu',
    'access arguments' => $access_arguments,
    'page callback' => 'page_manager_page_execute',
    'page arguments' => $page_arguments,
    'load arguments' => $load_arguments,
    'file' => 'plugins/tasks/page.inc',
  );

  if (isset($menu['title'])) {
    $item['title'] = $menu['title'];
  }
  if (isset($menu['weight'])) {
    $item['weight'] = $menu['weight'];
  }

  if (empty($menu['type'])) {
    $menu['type'] = 'none';
  }

  switch ($menu['type']) {
    case 'none':
    default:
      $item['type'] = MENU_CALLBACK;
      break;

    case 'normal':
      $item['type'] = MENU_NORMAL_ITEM;
      // Insert item into the proper menu
      $item['menu_name'] = $menu['name'];
      break;

    case 'tab':
      $item['type'] = MENU_LOCAL_TASK;
      break;

    case 'action':
      $item['type'] = MENU_LOCAL_ACTION;
      break;

    case 'default tab':
      $item['type'] = MENU_DEFAULT_LOCAL_TASK;
      break;
  }

  return $item;
}

/**
 * Page callback to add a subtask.
 */
function page_manager_page_add_subtask($task_name = NULL, $step = NULL) {
  ctools_include('context');
  $task = page_manager_get_task('page');
  $task_handler_plugins = page_manager_get_task_handler_plugins($task);
  if (empty($task_handler_plugins)) {
    drupal_set_message(t('There are currently no variants available and a page may not be added. Perhaps you need to install the Panels module to get a variant?'), 'error');
    return ' ';
  }

  $form_info = array(
    'id' => 'page_manager_add_page',
    'show trail' => TRUE,
    'show back' => TRUE,
    'show return' => FALSE,
    'next callback' => 'page_manager_page_add_subtask_next',
    'finish callback' => 'page_manager_page_add_subtask_finish',
    'return callback' => 'page_manager_page_add_subtask_finish',
    'cancel callback' => 'page_manager_page_add_subtask_cancel',
    'add order' => array(
      'basic' => t('Basic settings'),
      'argument' => t('Argument settings'),
      'access' => t('Access control'),
      'menu' => t('Menu settings'),
    ),
    'forms' => array(
      'basic' => array(
        'form id' => 'page_manager_page_form_basic',
      ),
      'access' => array(
        'form id' => 'page_manager_page_form_access',
      ),
      'menu' => array(
        'form id' => 'page_manager_page_form_menu',
      ),
      'argument' => array(
        'form id' => 'page_manager_page_form_argument',
      ),
    ),
  );

  if ($task_name) {
    $page = page_manager_get_page_cache($task_name);
    if (empty($page)) {
      return drupal_not_found();
    }

    $form_info['path'] = "admin/structure/pages/add/$task_name/%step";
  }
  else {
    $new_page = page_manager_page_new();
    $new_page->name = NULL;

    $page = new stdClass();
    page_manager_page_new_page_cache($new_page, $page);
    $form_info['path'] = 'admin/structure/pages/add/%task_name/%step';
  }

  if ($step && $step != 'basic') {
    $handler_plugin = page_manager_get_task_handler($page->handler);

    $form_info['forms'] += $handler_plugin['forms'];

    if (isset($page->forms)) {
      foreach ($page->forms as $id) {
        if (isset($form_info['add order'][$id])) {
          $form_info['order'][$id] = $form_info['add order'][$id];
        }
        else if (isset($handler_plugin['add features'][$id])) {
          $form_info['order'][$id] = $handler_plugin['add features'][$id];
        }
        else if (isset($handler_plugin['required forms'][$id])) {
          $form_info['order'][$id] = $handler_plugin['required forms'][$id];
        }
      }
    }
    else {
      $form_info['order'] = $form_info['add order'];
    }

    // This means we just submitted our form from the default list
    // of steps, which we've traded in for a newly generated list of
    // steps above. We need to translate this 'next' step into what
    // our questions determined would be next.
    if ($step == 'next') {
      $keys = array_keys($form_info['order']);
      // get rid of 'basic' from the list of forms.
      array_shift($keys);
      $step = array_shift($keys);

      // If $step == 'basic' at this point, we were not presented with any
      // additional forms at all. Let's just save and go!
      if ($step == 'basic') {
        page_manager_save_page_cache($page);
        // Redirect to the new page's task handler editor.
        drupal_goto(page_manager_edit_url($page->task_name));
      }
    }
  }
  else {
    $form_info['show trail'] = FALSE;
    $form_info['order'] = array(
      'basic' => t('Basic settings'),
      'next' => t('A meaningless second page'),
    );
  }

  ctools_include('wizard');
  $form_state = array(
    'task' => $task,
    'subtask' => $page->subtask,
    'page' => &$page,
    'type' => 'add',
    'task_id' => 'page',
    'task_name' => $page->task_name,
    'creating' => TRUE,
  );

  if (!empty($page->handlers)) {
    $keys = array_keys($page->handlers);
    $key = array_shift($keys);
    $form_state['handler'] = &$page->handlers[$key];
    $form_state['handler_id'] = $key;
  }

  $output = ctools_wizard_multistep_form($form_info, $step, $form_state);

  if (!$output) {
    // redirect.
    drupal_redirect_form(array(), $form_state['redirect']);
  }

  return $output;
}

/**
 * Callback generated when the add page process is finished.
 */
function page_manager_page_add_subtask_finish(&$form_state) {
  $page = &$form_state['page'];
  // Update the cache with changes.
  page_manager_set_page_cache($page);

  $handler = $form_state['handler'];
  $handler_plugin = page_manager_get_task_handler($handler->handler);

  // Redirect to the new page's task handler editor.
  if (isset($handler_plugin['add finish'])) {
    $form_state['redirect'] = page_manager_edit_url($page->task_name, array('handlers', $handler->name, $handler_plugin['add finish']));
  }
  else {
    $form_state['redirect'] = page_manager_edit_url($page->task_name);
  }
  return;
}

/**
 * Callback generated when the 'next' button is clicked.
 *
 * All we do here is store the cache.
 */
function page_manager_page_add_subtask_next(&$form_state) {
  if (empty($form_state['task_name']) || $form_state['task_name'] == 'page') {
    // We may not have known the path to go next, because we didn't yet know the
    // task name. This fixes that.
    $form_state['form_info']['path'] = str_replace('%task_name', $form_state['page']->task_name, $form_state['form_info']['path']);

    $form_state['redirect'] = ctools_wizard_get_path($form_state['form_info'], $form_state['clicked_button']['#next']);
  }

  // Update the cache with changes.
  page_manager_set_page_cache($form_state['page']);
}

/**
 * Callback generated when the 'cancel' button is clicked.
 *
 * All we do here is clear the cache.
 */
function page_manager_page_add_subtask_cancel(&$form_state) {
  // Wipe all our stored changes.
  if (isset($form_state['page']->task_name)) {
    page_manager_clear_page_cache($form_state['page']->task_name);
  }
}

/**
 * Basic settings form for a page manager page.
 */
function page_manager_page_form_basic($form, &$form_state) {
  $page = &$form_state['page']->subtask['subtask'];
  $task = $form_state['task'];

  $form['admin_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Administrative title'),
    '#description' => t('The name of this page. This will appear in the administrative interface to easily identify it.'),
    '#default_value' => $page->admin_title,
  );

  $form['name'] = array(
    '#type' => 'machine_name',
    '#title' => t('Machine name'),
    '#machine_name' => array(
      'exists' => 'page_manager_page_load',
      'source' => array('admin_title'),
    ),
    '#description' => t('The machine readable name of this page. It must be unique, and it must contain only alphanumeric characters and underscores. Once created, you will not be able to change this value!'),
    '#default_value' => $page->name,
  );

  if (isset($page->pid) || empty($form_state['creating'])) {
    $form['name']['#disabled'] = TRUE;
    $form['name']['#value'] = $page->name;
  }

  $form['admin_description'] = array(
    '#type' => 'textarea',
    '#title' => t('Administrative description'),
    '#description' => t('A description of what this page is, does or is for, for administrative use.'),
    '#default_value' => $page->admin_description,
  );

  // path
  $form['path'] = array(
    '#type' => 'textfield',
    '#title' => t('Path'),
    '#description' => t('The URL path to get to this page. You may create named placeholders for variable parts of the path by using %name for required elements and !name for optional elements. For example: "node/%node/foo", "forum/%forum" or "dashboard/!input". These named placeholders can be turned into contexts on the arguments form.'),
    '#default_value' => $page->path,
    '#field_prefix' => url(NULL, array('absolute' => TRUE)) . (variable_get('clean_url', 0) ? '' : '?q='),
  );

  $frontpage = variable_get('site_frontpage', 'node');

  $path = array();
  if ($page->path) {
    foreach (explode('/', $page->path) as $bit) {
      if ($bit[0] != '!') {
        $path[] = $bit;
      }
    }
  }

  $path = implode('/', $path);

  if (empty($path) || $path != $frontpage) {
    $form['frontpage'] = array(
      '#type' => 'checkbox',
      '#default_value' => !empty($page->make_frontpage),
      '#title' => t('Make this your site home page.'),
      '#description' => t('To set this panel as your home page you must create a unique path name with no % placeholders in the path. The site home page is currently set to %homepage on the !siteinfo configuration form.', array('!siteinfo' => l('Site Information', 'admin/settings/site-information'), '%homepage' => '/' . $frontpage)),
    );
  }
  else if ($path == $frontpage) {
    $form['frontpage_markup'] = array(
      '#value' => '<b>' . t('This page is currently set to be your site home page. This can be modified on the !siteinfo configuration form.', array('!siteinfo' => l('Site Information', 'admin/settings/site-information'))) . '</b>',
    );

    $form['frontpage'] = array(
      '#type' => 'value',
      '#value' => TRUE,
    );
  }

  if (!isset($page->pid) && !empty($form_state['creating'])) {
    $features['default'] = array(
      'access' => t('Access control'),
      'menu' => t('Visible menu item'),
    );

    module_load_include('inc', 'page_manager', 'page_manager.admin');
    $form = page_manager_handler_add_form($form, $form_state, $features);
  }

  return $form;
}

function page_manager_page_form_basic_validate_filter($value) {
  return $value === -1;
}

/**
 * Validate the basic form.
 */
function page_manager_page_form_basic_validate(&$form, &$form_state) {
  // Ensure path is unused by other pages.
  $page = $form_state['page']->subtask['subtask'];
  $name = !empty($form_state['values']['name']) ? $form_state['values']['name'] : $page->name;
  if (empty($name)) {
    form_error($form['name'], t('Name is required.'));
  }

  // If this is new, make sure the name is unique:
  if (empty($page->name)) {
    $test = page_manager_page_load($name);
    if ($test) {
      form_error($form['name'], t('That name is used by another page: @page', array('@page' => $test->admin_title)));
    }

    // Ensure name fits the rules:
    if (preg_match('/[^a-zA-Z0-9_]/', $form_state['values']['name'])) {
      form_error($form['name'], t('Page name must be alphanumeric or underscores only.'));
    }
  }

  $pages = page_manager_page_load_all();
  foreach ($pages as $test) {
    if ($test->name != $name && $test->path == $form_state['values']['path'] && empty($test->disabled)) {
      form_error($form['path'], t('That path is used by another page: @page', array('@page' => $test->admin_title)));
    }
  }

  // Ensure path is unused by things NOT pages. We do the double check because
  // we're checking against our page callback.
  $path = array();
  if (empty($form_state['values']['path'])) {
    form_error($form['path'], t('Path is required.'));
    // stop processing here if there is no path.
    return;
  }

  $found = FALSE;
  $error = FALSE;
  foreach (explode('/', $form_state['values']['path']) as $position => $bit) {
    if (!isset($bit) || $bit === '') {
      continue;
    }

    if ($bit == '%' || $bit == '!') {
      form_error($form['path'], t('You cannot have an unnamed placeholder (% or ! by itself). Please name your placeholder by adding a short piece of descriptive text to the % or !, such as %user or %node.'));
    }

    if ($bit[0] == '%') {
      if ($found) {
        form_error($form['path'], t('You cannot have a dynamic path element after an optional path element.'));
      }

      if ($position == 0) {
        form_error($form['path'], t('The first element in a path may not be dynamic.'));
      }

      $path[] = '%';
    }
    else if ($bit[0] == '!') {
      $found = TRUE;
    }
    else {
      if ($found) {
        form_error($form['path'], t('You cannot have a static path element after an optional path element.'));
      }
      $path[] = $bit;
    }
  }

  // Check to see if something that isn't a page manager page is using the path.
  $path = implode('/', $path);
  $result = db_query('SELECT * FROM {menu_router} WHERE path = :path', array(':path' => $path));
  foreach ($result as $router) {
    if ($router->page_callback != 'page_manager_page_execute') {
      form_error($form['path'], t('That path is already in use. This system cannot override existing paths.'));
    }
  }

  // Ensure the path is not already an alias to something else.
  if (strpos($path, '%') === FALSE) {
    $alias = db_query('SELECT alias, source FROM {url_alias} WHERE alias = :path', array(':path' => $path))->fetchObject();
    if ($alias) {
      form_error($form['path'], t('That path is currently assigned to be an alias for @alias. This system cannot override existing aliases.', array('@alias' => $alias->src)));
    }
  }
  else {
    if (!empty($form_state['values']['frontpage'])) {
      form_error($form['path'], t('You cannot make this page your site home page if it uses % placeholders.'));
    }
  }

  // Ensure path is properly formed.
  $args = page_manager_page_get_named_arguments($form_state['values']['path']);
  if ($invalid_args = array_filter($args, 'page_manager_page_form_basic_validate_filter')) {
    foreach ($invalid_args as $arg => $position) {
      form_error($form['path'], t('Duplicated argument %arg', array('%arg' => $arg)));
    }
  }

  if (isset($args['%'])) {
    form_error($form['path'], t('Invalid arg <em>%</em>. All arguments must be named with keywords.'));
  }

  $form_state['arguments'] = $args;
}

/**
 * Store the values from the basic settings form.
 */
function page_manager_page_form_basic_submit(&$form, &$form_state) {
  $page = &$form_state['page']->subtask['subtask'];
  $cache = &$form_state['page'];

  // If this is a new thing, then we have to do a bunch of setup to create
  // the cache record with the right ID and some basic data that we could
  // not know until we asked the user some questions.
  if (!isset($page->pid) && !empty($form_state['creating'])) {
    // Update the data with our new name.
    $page->name = $form_state['values']['name'];
    $form_state['page']->task_name = page_manager_make_task_name($form_state['task_id'], $page->name);
    $cache->handler = $form_state['values']['handler'];
    $cache->subtask_id = $page->name;
    $plugin = page_manager_get_task_handler($cache->handler);

    // If they created and went back, there might be old, dead handlers
    // that are not going to be added.
    //
    // Remove them:
    $cache->handlers = array();
    $cache->handler_info = array();

    // Create a new handler.
    $handler = page_manager_new_task_handler($plugin);
    $title = !empty($form_state['values']['title']) ? $form_state['values']['title'] : $plugin['title'];
    page_manager_handler_add_to_page($cache, $handler, $title);

    // Figure out which forms to present them with
    $cache->forms = array();
    $cache->forms[] = 'basic'; // This one is always there.
    if (!empty($form_state['arguments'])) {
      $cache->forms[] = 'argument';
    }

    $features = $form_state['values']['features'];
    $cache->forms = array_merge($cache->forms, array_keys(array_filter($features['default'])));
    if (isset($features[$form_state['values']['handler']])) {
      $cache->forms = array_merge($cache->forms, array_keys(array_filter($features[$form_state['values']['handler']])));
    }

    if (isset($plugin['required forms'])) {
      $cache->forms = array_merge($cache->forms, array_keys($plugin['required forms']));
    }
  }

  $page->admin_title = $form_state['values']['admin_title'];
  $cache->subtask['admin title'] = check_plain($form_state['values']['admin_title']);

  $page->admin_description = $form_state['values']['admin_description'];
  $cache->subtask['admin description'] = filter_xss_admin($form_state['values']['admin_description']);

  if ($page->path != $form_state['values']['path']) {
    $page->path = $form_state['values']['path'];
    page_manager_page_recalculate_arguments($page);
    $cache->path_changed = TRUE;
  }

  $page->make_frontpage = !empty($form_state['values']['frontpage']);
}

/**
 * Form to handle menu item controls.
 */
function page_manager_page_form_menu($form, &$form_state) {
  ctools_include('dependent');
  $form['menu'] = array(
    '#prefix' => '<div class="clearfix">',
    '#suffix' => '</div>',
    '#tree' => TRUE,
  );

  $menu = $form_state['page']->subtask['subtask']->menu;
  if (empty($menu)) {
    $menu = array(
      'type' => 'none',
      'title' => '',
      'weight' => 0,
      'name' => 'navigation',
      'parent' => array(
        'type' => 'none',
        'title' => '',
        'weight' => 0,
        'name' => 'navigation',
      ),
    );
  }

  $form['menu']['type'] = array(
    '#title' => t('Type'),
    '#type' => 'radios',
    '#options' => array(
      'none' => t('No menu entry'),
      'normal' => t('Normal menu entry'),
      'tab' => t('Menu tab'),
      'default tab' => t('Default menu tab'),
      'action' => t('Local action'),
    ),
    '#default_value' => $menu['type'],
  );

  $form['menu']['title'] = array(
    '#title' => t('Title'),
    '#type' => 'textfield',
    '#default_value' => $menu['title'],
    '#description' => t('If set to normal or tab, enter the text to use for the menu item.'),
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('radio:menu[type]' => array('normal', 'tab', 'default tab', 'action')),
  );

  list($major, $minor) = explode('.', VERSION, 2);

  // Only display the menu selector if menu module is enabled.
  if (module_exists('menu')) {
    $form['menu']['name'] = array(
      '#title' => t('Menu'),
      '#type' => 'select',
      '#options' => menu_get_menus(),
      '#default_value' => $menu['name'],
      '#description' => t('Insert item into an available menu.'),
      '#process' => array('ctools_dependent_process'),
      '#dependency' => array('radio:menu[type]' => array('normal')),
    );
  }
  else {
    $form['menu']['name'] = array(
      '#type' => 'value',
      '#value' => $menu['name'],
    );
    $form['menu']['markup'] = array(
      '#value' => t('Menu selection requires the activation of menu module.'),
    );
  }
  $form['menu']['weight'] = array(
    '#title' => t('Weight'),
    '#type' => 'textfield',
    '#default_value' => isset($menu['weight']) ? $menu['weight'] : 0,
    '#description' => t('The lower the weight the higher/further left it will appear.'),
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('radio:menu[type]' => array('normal', 'tab', 'default tab', 'action')),
  );

  $form['menu']['parent']['type'] = array(
    '#prefix' => '<div id="edit-menu-parent-type-wrapper">',
    '#suffix' => '</div>',
    '#title' => t('Parent menu item'),
    '#type' => 'radios',
    '#options' => array('none' => t('Already exists'), 'normal' => t('Normal menu item'), 'tab' => t('Menu tab')),
    '#default_value' => $menu['parent']['type'],
    '#description' => t('When providing a menu item as a default tab, Drupal needs to know what the parent menu item of that tab will be. Sometimes the parent will already exist, but other times you will need to have one created. The path of a parent item will always be the same path with the last part left off. i.e, if the path to this view is <em>foo/bar/baz</em>, the parent path would be <em>foo/bar</em>.'),
    '#process' => array('form_process_radios', 'ctools_dependent_process'),
    '#dependency' => array('radio:menu[type]' => array('default tab')),
  );
  $form['menu']['parent']['title'] = array(
    '#title' => t('Parent item title'),
    '#type' => 'textfield',
    '#default_value' => $menu['parent']['title'],
    '#description' => t('If creating a parent menu item, enter the title of the item.'),
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('radio:menu[type]' => array('default tab'), 'radio:menu[parent][type]' => array('normal', 'tab')),
    '#dependency_count' => 2,
  );
  // Only display the menu selector if menu module is enabled.
  if (module_exists('menu')) {
    $form['menu']['parent']['name'] = array(
      '#title' => t('Parent item menu'),
      '#type' => 'select',
      '#options' => menu_get_menus(),
      '#default_value' => $menu['parent']['name'],
      '#description' => t('Insert item into an available menu.'),
      '#process' => array('ctools_dependent_process'),
      '#dependency' => array('radio:menu[type]' => array('default tab'), 'radio:menu[parent][type]' => array('normal')),
      '#dependency_count' => 2,
    );
  }
  else {
    $form['menu']['parent']['name'] = array(
      '#type' => 'value',
      '#value' => $menu['parent']['name'],
    );
  }
  $form['menu']['parent']['weight'] = array(
    '#title' => t('Tab weight'),
    '#type' => 'textfield',
    '#default_value' => $menu['parent']['weight'],
    '#size' => 5,
    '#description' => t('If the parent menu item is a tab, enter the weight of the tab. The lower the number, the more to the left it will be.'),
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('radio:menu[type]' => array('default tab'), 'radio:menu[parent][type]' => array('tab')),
    '#dependency_count' => 2,
  );

  return $form;
}

/**
 * Validate handler for the menu form for add/edit page task.
 */
function page_manager_page_form_menu_validate(&$form, &$form_state) {
  // If setting a 'normal' menu entry, make sure that any placeholders
  // support the to_arg stuff.

  if ($form_state['values']['menu']['type'] == 'normal') {
    $page = $form_state['page']->subtask['subtask'];

    foreach (explode('/', $page->path) as $bit) {
      if (!isset($bit) || $bit === '') {
        continue;
      }

      if ($bit[0] == '%') {
        // Chop off that %.
        $name = substr($bit, 1);

        // Check to see if the argument plugin allows to arg:
        if (!empty($page->arguments[$name])) {
          ctools_include('context');
          $plugin = ctools_get_argument($page->arguments[$name]['name']);
          if (!empty($plugin['path placeholder to_arg'])) {
            continue;
          }
        }

        form_error($form['menu']['type'], t('Paths with non optional placeholders cannot be used as normal menu items unless the selected argument handler provides a default argument to use for the menu item.'));
        return;
      }
    }
  }
}

/**
 * Submit handler for the menu form for add/edit page task.
 */
function page_manager_page_form_menu_submit(&$form, &$form_state) {
  $form_state['page']->subtask['subtask']->menu = $form_state['values']['menu'];
  $form_state['page']->path_changed = TRUE;
}

/**
 * Form to handle menu item controls.
 */
function page_manager_page_form_access($form, &$form_state) {
  ctools_include('context');
  $form_state['module'] = 'page_manager_page';
  $form_state['callback argument'] = $form_state['page']->task_name;
  $form_state['access'] = $form_state['page']->subtask['subtask']->access;
  $form_state['no buttons'] = TRUE;
  $form_state['contexts'] = array();

  // Load contexts based on argument data:
  if ($arguments = _page_manager_page_get_arguments($form_state['page']->subtask['subtask'])) {
    $form_state['contexts'] = ctools_context_get_placeholders_from_argument($arguments);
  }

  ctools_include('context-access-admin');
  $form = ctools_access_admin_form($form, $form_state);

  return $form;
}

/**
 * Submit handler to deal with access control changes.
 */
function page_manager_page_form_access_submit(&$form, &$form_state) {
  $form_state['page']->subtask['subtask']->access['logic'] = $form_state['values']['logic'];
  $form_state['page']->path_changed = TRUE;
}

/**
 * Form to handle assigning argument handlers to named arguments.
 */
function page_manager_page_form_argument($form, &$form_state) {
  $page = &$form_state['page']->subtask['subtask'];
  $path = $page->path;

  $arguments = page_manager_page_get_named_arguments($path);

  $form['table'] = array(
    '#theme' => 'page_manager_page_form_argument_table',
    '#page-manager-path' => $path,
    'argument' => array(),
  );

  $task_name = $form_state['page']->task_name;
  foreach ($arguments as $keyword => $position) {
    $conf = array();

    if (isset($page->temporary_arguments[$keyword]) && !empty($form_state['allow temp'])) {
      $conf = $page->temporary_arguments[$keyword];
    }
    else if (isset($page->arguments[$keyword])) {
      $conf = $page->arguments[$keyword];
    }

    $context = t('No context assigned');

    $plugin = array();
    if ($conf && isset($conf['name'])) {
      ctools_include('context');
      $plugin = ctools_get_argument($conf['name']);

      if (isset($plugin['title'])) {
        $context = $plugin['title'];
      }
    }

    $form['table']['argument'][$keyword]['#keyword'] = $keyword;
    $form['table']['argument'][$keyword]['#position'] = $position;
    $form['table']['argument'][$keyword]['#context'] = $context;

    // The URL for this ajax button
    $form['table']['argument'][$keyword]['change-url'] = array(
      '#attributes' => array('class' => array("page-manager-context-$keyword-change-url")),
      '#type' => 'hidden',
      '#value' => url("admin/structure/pages/argument/change/$task_name/$keyword", array('absolute' => TRUE)),
    );
    $form['table']['argument'][$keyword]['change'] = array(
      '#type' => 'submit',
      '#value' => t('Change'),
      '#attributes' => array('class' => array('ctools-use-modal')),
      '#id' => "page-manager-context-$keyword-change",
    );

    $form['table']['argument'][$keyword]['settings'] = array();

    // Only show the button if this has a settings form available:
    if (!empty($plugin)) {
      // The URL for this ajax button
      $form['table']['argument'][$keyword]['settings-url'] = array(
        '#attributes' => array('class' => array("page-manager-context-$keyword-settings-url")),
        '#type' => 'hidden',
        '#value' => url("admin/structure/pages/argument/settings/$task_name/$keyword", array('absolute' => TRUE)),
      );
      $form['table']['argument'][$keyword]['settings'] = array(
        '#type' => 'submit',
        '#value' => t('Settings'),
        '#attributes' => array('class' => array('ctools-use-modal')),
        '#id' => "page-manager-context-$keyword-settings",
      );
    }
  }

  return $form;
}

/**
 * Theme the table for this form.
 */
function theme_page_manager_page_form_argument_table($vars) {
  $form = $vars['form'];
  $header = array(
    array('data' => t('Argument'), 'class' => array('page-manager-argument')),
    array('data' => t('Position in path'), 'class' => array('page-manager-position')),
    array('data' => t('Context assigned'), 'class' => array('page-manager-context')),
    array('data' => t('Operations'), 'class' => array('page-manager-operations')),
  );

  $rows = array();

  ctools_include('modal');
  ctools_modal_add_js();
  foreach (element_children($form['argument']) as $key) {
    $row = array();
    $row[] = '%' . check_plain($form['argument'][$key]['#keyword']);
    $row[] = check_plain($form['argument'][$key]['#position']);
    $row[] = $form['argument'][$key]['#context'] . ' &nbsp; ' . drupal_render($form['argument'][$key]['change']);;
    $row[] = drupal_render($form['argument'][$key]['settings']) . drupal_render($form['argument'][$key]);

    $rows[] = array('data' => $row);
  }

  if (!$rows) {
    $rows[] = array(array('data' => t('The path %path has no arguments to configure.', array('%path' => $form['#page-manager-path'])), 'colspan' => 4));
  }

  $attributes = array(
    'id' => 'page-manager-argument-table',
  );

  $output = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => $attributes));
  return $output;
}

/**
 * Ajax entry point to edit an item
 */
function page_manager_page_subtask_argument_ajax($step = NULL, $task_name = NULL, $keyword = NULL) {
  ctools_include('ajax');
  ctools_include('modal');
  ctools_include('context');
  ctools_include('wizard');

  if (!$step) {
    return ctools_ajax_render_error();
  }

  if (!$cache = page_manager_get_page_cache($task_name)) {
    return ctools_ajax_render_error(t('Invalid object name.'));
  }

  $page = &$cache->subtask['subtask'];
  $path = $page->path;
  $arguments = page_manager_page_get_named_arguments($path);

  // Load stored object from cache.
  if (!isset($arguments[$keyword])) {
    return ctools_ajax_render_error(t('Invalid keyword.'));
  }

  // Set up wizard info
  $form_info = array(
    'id' => 'page_manager_page_argument',
    'path' => "admin/structure/pages/argument/%step/$task_name/$keyword",
    'show cancel' => TRUE,
    'next callback' => 'page_manager_page_argument_next',
    'finish callback' => 'page_manager_page_argument_finish',
    'cancel callback' => 'page_manager_page_argument_cancel',
    'order' => array(
      'change' => t('Change context type'),
      'settings' => t('Argument settings'),
    ),
    'forms' => array(
      'change' => array(
        'title' => t('Change argument'),
        'form id' => 'page_manager_page_argument_form_change',
      ),
      'settings' => array(
        'title' => t('Argument settings'),
        'form id' => 'page_manager_page_argument_form_settings',
      ),
    ),
  );

  $form_state = array(
    'page' => $cache,
    'keyword' => $keyword,
    'ajax' => TRUE,
    'modal' => TRUE,
    'modal return' => TRUE,
    'commands' => array(),
  );

  $output = ctools_wizard_multistep_form($form_info, $step, $form_state);
  if (!empty($form_state['complete'])) {
    if (isset($page->temporary_arguments[$keyword])) {
      $page->arguments[$keyword] = $page->temporary_arguments[$keyword];
    }

    if (isset($page->temporary_arguments)) {
      unset($page->temporary_arguments);
    }

    // Update the cache with changes.
    page_manager_set_page_cache($cache);

    // Rerender the table so we can ajax it back in.
    // Go directly to the form and retrieve it using a blank form and
    // a clone of our current form state. This is an abbreviated
    // drupal_get_form that is halted prior to render and is never
    // fully processed, but is guaranteed to produce the same form we
    // started with so we don't have to do crazy stuff to rerender
    // just part of it.

    // @todo should there be a tool to do this?

    $clone_state = $form_state;
    $clone_state['allow temp'] = TRUE;
    page_manager_page_form_argument($form, $clone_state);
    $form = drupal_build_form('page_manager_page_form_argument', $form_state);

    // Render just the table portion.
    $output = drupal_render($form['table']);
    $commands = array(
      ajax_command_replace('#page-manager-argument-table', $output),
      ctools_modal_command_dismiss(),
    );
  }
  else {
    $commands = ctools_modal_form_render($form_state, $output);
  }
  print ajax_render($commands);
  ajax_footer();
  exit;
}

/**
 * Callback generated when the add page process is finished.
 */
function page_manager_page_argument_finish(&$form_state) {
}

/**
 * Callback generated when the 'next' button is clicked.
 *
 * All we do here is store the cache.
 */
function page_manager_page_argument_next(&$form_state) {
  // Update the cache with changes.
  page_manager_set_page_cache($form_state['page']);
}

/**
 * Callback generated when the 'cancel' button is clicked.
 *
 * We might have some temporary data lying around. We must remove it.
 */
function page_manager_page_argument_cancel(&$form_state) {
  $page = &$form_state['page']->subtask['subtask'];
  if (isset($page->temporary_arguments)) {
    unset($page->temporary_arguments);
    // Update the cache with changes.
    page_manager_set_page_cache($page);
  }
}

/**
 * Basic settings form for a page manager page.
 */
function page_manager_page_argument_form_change($form, &$form_state) {
  $page = &$form_state['page']->subtask['subtask'];
  $keyword = &$form_state['keyword'];

  ctools_include('context');
  $plugins = ctools_get_arguments();

  $options = array();
  foreach ($plugins as $id => $plugin) {
    $options[$id] = $plugin['title'];
  }

  asort($options);

  $options = array('' => t('No context selected')) + $options;

  $argument = '';
  if (isset($page->arguments[$keyword]) && isset($page->arguments[$keyword]['name'])) {
    $argument = $page->arguments[$keyword]['name'];
  }

  $form['argument'] = array(
    '#type' => 'radios',
    '#options' => $options,
    '#default_value' => $argument,
  );

  return $form;
}

/**
 * Submit handler to change an argument.
 */
function page_manager_page_argument_form_change_submit(&$form, &$form_state) {
  $page     = &$form_state['page']->subtask['subtask'];
  $keyword  = &$form_state['keyword'];
  $argument = $form_state['values']['argument'];

  // If the argument is not changing, we do not need to do anything.
  if (isset($page->arguments[$keyword]['name']) && $page->arguments[$keyword]['name'] == $argument) {
    // Set the task to cancel since no change means do nothing:
    $form_state['clicked_button']['#wizard type'] = 'cancel';
    return;
  }

  ctools_include('context');

  // If switching to the no context, just wipe out the old data.
  if (empty($argument)) {
    $form_state['clicked_button']['#wizard type'] = 'finish';
    $page->temporary_arguments[$keyword] = array(
      'settings' => array(),
      'identifier' => t('No context'),
    );
    return;
  }

  $plugin = ctools_get_argument($argument);

  // Acquire defaults.
  $settings = array();

  if (isset($plugin['default'])) {
    if (is_array($plugin['default'])) {
      $settings = $plugin['default'];
    }
    else if (function_exists($plugin['default'])) {
      $settings = $plugin['default']();
    }
  }

  $id = ctools_context_next_id($page->arguments, $argument);
  $title = isset($plugin['title']) ? $plugin['title'] : t('No context');

  // Set the new argument in a temporary location.
  $page->temporary_arguments[$keyword] = array(
    'id' => $id,
    'identifier' => $title . ($id > 1 ? ' ' . $id : ''),
    'name' => $argument,
    'settings' => $settings,
  );
}

/**
 * Basic settings form for a page manager page.
 */
function page_manager_page_argument_form_settings($form, &$form_state) {
  $page = &$form_state['page']->subtask['subtask'];
  $keyword = &$form_state['keyword'];

  if (isset($page->temporary_arguments[$keyword])) {
    $conf = $page->temporary_arguments[$keyword];
  }
  else if (isset($page->arguments[$keyword])) {
    $conf = $page->temporary_arguments[$keyword] = $page->arguments[$keyword];
  }

  if (!isset($conf)) {
    // This should be impossible and thus never seen.
    $form['error'] = array('#value' => t('Error: missing argument.'));
    return;
  }

  ctools_include('context');
  $plugin = ctools_get_argument($conf['name']);

  $form['settings'] = array(
    '#tree' => TRUE,
  );

  $form['identifier'] = array(
    '#type' => 'textfield',
    '#title' => t('Context identifier'),
    '#description' => t('This is the title of the context used to identify it later in the administrative process. This will never be shown to a user.'),
    '#default_value' => $conf['identifier'],
  );

  if (!$plugin) {
    // This should be impossible and thus never seen.
    $form['error'] = array('#value' => t('Error: missing or invalid argument plugin %argument.', array('%argument', $argument)));
    return;
  }

  if ($function = ctools_plugin_get_function($plugin, 'settings form')) {
    $function($form, $form_state, $conf['settings']);
  }

  $form_state['plugin'] = $plugin;
  return $form;
}

/**
 * Validate handler for argument settings.
 */
function page_manager_page_argument_form_settings_validate(&$form, &$form_state) {
  if ($function = ctools_plugin_get_function($form_state['plugin'], 'settings form validate')) {
    $function($form, $form_state);
  }
}

/**
 * Submit handler for argument settings.
 */
function page_manager_page_argument_form_settings_submit(&$form, &$form_state) {
  if ($function = ctools_plugin_get_function($form_state['plugin'], 'settings form submit')) {
    $function($form, $form_state);
  }

  $page = &$form_state['page']->subtask['subtask'];
  $keyword = &$form_state['keyword'];
  // Copy the form to our temporary location which will get moved again when
  // finished. Yes, finished is always next but finish can happen from other
  // locations so we funnel through that path rather than duplicate.
  $page->temporary_arguments[$keyword]['identifier'] = $form_state['values']['identifier'];
  if (isset($form_state['values']['settings'])) {
    $page->temporary_arguments[$keyword]['settings'] = $form_state['values']['settings'];
  }
  else {
    $page->temporary_arguments[$keyword]['settings'] = array();
  }
}

/**
 * Import a task handler from cut & paste
 */
function page_manager_page_import_subtask($form, &$form_state, $task_name) {
  $form_state['task'] = page_manager_get_task($task_name);

  drupal_set_title(t('Import page'));
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Page name'),
    '#description' => t('Enter the name to use for this page if it is different from the source page. Leave blank to use the original name of the page.'),
  );

  $form['path'] = array(
    '#type' => 'textfield',
    '#title' => t('Path'),
    '#description' => t('Enter the path to use for this page if it is different from the source page. Leave blank to use the original path of the page.'),
  );

  $form['overwrite'] = array(
    '#type' => 'checkbox',
    '#title' => t('Allow overwrite of an existing page'),
    '#description' => t('If the name you selected already exists in the database, this page will be allowed to overwrite the existing page.'),
  );

  $form['object'] = array(
    '#type' => 'textarea',
    '#title' => t('Paste page code here'),
    '#rows' => 15,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Import'),
  );
  return $form;
}

/**
 * Ensure we got a valid page.
 */
function page_manager_page_import_subtask_validate(&$form, &$form_state) {
  ob_start();
  eval($form_state['values']['object']);
  ob_end_clean();

  if (!isset($page) || !is_object($page)) {
    $errors = ob_get_contents();
    if (empty($errors)) {
      $errors = t('No handler found.');
    }
    form_error($form['object'], t('Unable to get a page from the import. Errors reported: @errors', array('@errors' => $errors)));
  }

  if (empty($form_state['values']['name'])) {
    $form_state['values']['name'] = $page->name;
  }

  $task_name = page_manager_make_task_name('page', $form_state['values']['name']);
  $form_state['cache'] = page_manager_get_page_cache($task_name);

  if ($form_state['cache'] && $form_state['cache']->locked) {
    form_error($form['name'], t('That page name is in use and locked by another user. You must <a href="!break">break the lock</a> on that page before proceeding, or choose a different name.', array('!break' => url(page_manager_edit_url($task_name, array('actions', 'break-lock'))))));
    return;
  }

  if (empty($form_state['values']['path'])) {
    $form_state['values']['path'] = $page->path;
  }

  if (empty($form_state['values']['overwrite'])) {
    $page->name = NULL;
  }

  $form_state['page'] = new stdClass();
  $form_state['page']->subtask['subtask'] = $page;
  page_manager_page_form_basic_validate($form, $form_state);
}

/**
 * Submit the import page to create the new page and redirect.
 */
function page_manager_page_import_subtask_submit($form, &$form_state) {
  $page = &$form_state['page']->subtask['subtask'];
  $page->name = $form_state['values']['name'];
  $page->path = $form_state['values']['path'];

  $task_name = page_manager_make_task_name('page', $page->name);
  $cache = page_manager_get_page_cache($task_name);
  if (!$cache) {
    $cache = new stdClass();
  }

  page_manager_page_new_page_cache($page, $cache);
  page_manager_set_page_cache($cache);

  $form_state['redirect'] = page_manager_edit_url($task_name);
}

/**
 * Entry point to export a page.
 */
function page_manager_page_form_export($form, &$form_state) {
  $page = $form_state['page']->subtask['subtask'];

  $export = page_manager_page_export($page, $form_state['page']->handlers);

  $lines = substr_count($export, "\n");
  $form['code'] = array(
    '#type' => 'textarea',
    '#default_value' => $export,
    '#rows' => $lines,
  );

  unset($form['buttons']);
  return $form;
}

/**
 * Entry point to clone a page.
 */
function page_manager_page_form_clone($form, &$form_state) {
  $page = &$form_state['page']->subtask['subtask'];

  // This provides its own button because it does something totally different.
  unset($form['buttons']);

  $form['admin_title'] = array(
    '#type' => 'textfield',
    '#title' => t('Administrative title'),
    '#description' => t('The name of this page. This will appear in the administrative interface to easily identify it.'),
    '#default_value' => $page->admin_title,
  );
  
  $form['name'] = array(
    '#type' => 'machine_name',
    '#title' => t('Page name'),
    '#machine_name' => array(
      'exists' => 'page_manager_page_load',
      'source' => array('admin_title'),
    ),
    '#description' => t('Enter the name to the new page It must be unique and contain only alphanumeric characters and underscores.'),
  );

  // path
  $form['path'] = array(
    '#type' => 'textfield',
    '#title' => t('Path'),
    '#description' => t('The URL path to get to this page. You may create named placeholders for variable parts of the path by using %name for required elements and !name for optional elements. For example: "node/%node/foo", "forum/%forum" or "dashboard/!input". These named placeholders can be turned into contexts on the arguments form. You cannot use the same path as the original page.'),
    '#default_value' => $page->path,
  );

  $form['handlers'] = array(
    '#type' => 'checkbox',
    '#title' => t('Clone variants'),
    '#description' => t('If checked all variants associated with the page will be cloned as well. If not checked the page will be cloned without variants.'),
    '#default_value' => TRUE,
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Clone'),
  );

  return $form;
}

/**
 * Validate clone page form.
 */
function page_manager_page_form_clone_validate(&$form, &$form_state) {
  $page = &$form_state['page']->subtask['subtask'];

  $page->old_name = $page->name;
  $page->name = NULL;
  page_manager_page_form_basic_validate($form, $form_state);
}

/**
 * submit clone page form.
 *
 * Load the page, change the name(s) to protect the innocent, and if
 * requested, load all the task handlers so that they get saved properly too.
 */
function page_manager_page_form_clone_submit(&$form, &$form_state) {
  $original = $form_state['page']->subtask['subtask'];

  $original->name = $form_state['values']['name'];
  $original->admin_title = $form_state['values']['admin_title'];
  $original->path = $form_state['values']['path'];

  $handlers = !empty($form_state['values']['handlers']) ? $form_state['page']->handlers : FALSE;
  // Export the handler, which is a fantastic way to clean database IDs out of it.
  $export = page_manager_page_export($original, $handlers);
  ob_start();
  eval($export);
  ob_end_clean();

  $task_name = page_manager_make_task_name('page', $page->name);
  $cache = new stdClass();

  page_manager_page_new_page_cache($page, $cache);
  page_manager_set_page_cache($cache);

  $form_state['redirect'] = page_manager_edit_url($task_name);
}

/**
 * Entry point to export a page.
 */
function page_manager_page_form_delete($form, &$form_state) {
  $page = &$form_state['page']->subtask['subtask'];

  if ($page->type == t('Overridden')) {
    $text = t('Reverting the page will delete the page that is in the database, reverting it to the original default page. Any changes you have made will be lost and cannot be recovered.');
  }
  else {
    $text = t('Are you sure you want to delete this page? Deleting a page cannot be undone.');
  }
  $form['markup'] = array(
    '#value' => '<p>' . $text . '</p>',
  );

  if (empty($form_state['page']->locked)) {
    unset($form['buttons']);
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => $page->type == t('Overridden') ? t('Revert') : t('Delete'),
    );
  }

  return $form;
}

/**
 * Submit handler to delete a view.
 */
function page_manager_page_form_delete_submit(&$form, &$form_state) {
  $page = $form_state['page']->subtask['subtask'];
  page_manager_page_delete($page);
  if ($page->type != t('Overridden')) {
    $form_state['redirect'] = 'admin/structure/pages';
    drupal_set_message(t('The page has been deleted.'));
  }
  else {
    $form_state['redirect'] = page_manager_edit_url($form_state['page']->task_name, array('summary'));
    drupal_set_message(t('The page has been reverted.'));
  }
}
