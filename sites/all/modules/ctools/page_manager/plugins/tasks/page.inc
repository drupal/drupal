<?php
// $Id: page.inc,v 1.24 2010/10/11 22:18:23 sdboyer Exp $

/**
 * @file
 * Handle the 'page' task, which creates pages with arbitrary tasks and lets
 * handlers decide how they will be rendered.
 *
 * This creates subtasks and stores them in the page_manager_pages table. These
 * are exportable objects, too.
 *
 * The render callback for this task type has $handler, $page, $contexts as
 * parameters.
 */

/**
 * Specialized implementation of hook_page_manager_task_tasks(). See api-task.html for
 * more information.
 */
function page_manager_page_page_manager_tasks() {
  return array(
    'title' => t('Custom pages'),
    'description' => t('Administrator created pages that have a URL path, access control and entries in the Drupal menu system.'),
    'non-exportable' => TRUE,
    'subtasks' => TRUE,
    'subtask callback' => 'page_manager_page_subtask',
    'subtasks callback' => 'page_manager_page_subtasks',
    'save subtask callback' => 'page_manager_page_save_subtask',
    'hook menu' => array(
      'file' => 'page.admin.inc',
      'path' => drupal_get_path('module', 'page_manager') . '/plugins/tasks',
      'function' => 'page_manager_page_menu',
    ),
    'hook theme' => 'page_manager_page_theme',
    // page only items
    'task type' => 'page',
    'page operations' => array(
      array(
        'title' => ' &raquo; ' . t('Create a new page'),
        'href' => 'admin/structure/pages/add',
        'html' => TRUE,
      ),
    ),
    'columns' => array(
      'storage' => array(
        'label' => t('Storage'),
        'class' => 'page-manager-page-storage',
      ),
    ),
    'page type' => 'custom',

    // context only items
    'handler type' => 'context',
    'get arguments' => array(
      'file' => 'page.admin.inc',
      'path' => drupal_get_path('module', 'page_manager') . '/plugins/tasks',
      'function' => 'page_manager_page_get_arguments',
    ),
    'get context placeholders' => 'page_manager_page_get_contexts',
    'access restrictions' => 'page_manager_page_access_restrictions',
    'uses handlers' => TRUE,
  );
}

/**
 * Task callback to get all subtasks.
 *
 * Return a list of all subtasks.
 */
function page_manager_page_subtasks($task) {
  $pages = page_manager_page_load_all($task['name']);
  $return = array();
  foreach ($pages as $name => $page) {
    $return[$name] = page_manager_page_build_subtask($task, $page);
  }

  return $return;
}

/**
 * Callback to return a single subtask.
 */
function page_manager_page_subtask($task, $subtask_id) {
  $page = page_manager_page_load($subtask_id);
  if ($page) {
    return page_manager_page_build_subtask($task, $page);
  }
}

/**
 * Call back from the administrative system to save a page.
 *
 * We get the $subtask as created by page_manager_page_build_subtask.
 */
function page_manager_page_save_subtask($subtask) {
  $page = &$subtask['subtask'];

  // Ensure $page->arguments contains only real arguments:
  $arguments = page_manager_page_get_named_arguments($page->path);
  $args = array();
  foreach ($arguments as $keyword => $position) {
    if (isset($page->arguments[$keyword])) {
      $args[$keyword] = $page->arguments[$keyword];
    }
    else {
      $args[$keyword] = array(
        'id' => '',
        'identifier' => '',
        'argument' => '',
        'settings' => array(),
      );
    }
  }
  page_manager_page_recalculate_arguments($page);
  // Create a real object from the cache
  page_manager_page_save($page);

  // Check to see if we should make this the site frontpage.
  if (!empty($page->make_frontpage)) {
    $path = array();
    foreach (explode('/', $page->path) as $bit) {
      if ($bit[0] != '!') {
        $path[] = $bit;
      }
    }

    $path = implode('/', $path);
    $front = variable_get('site_frontpage', 'node');
    if ($path != $front) {
      variable_set('site_frontpage', $path);
    }
  }
}

/**
 * Build a subtask array for a given page.
 */
function page_manager_page_build_subtask($task, $page) {
  $operations = array();
  $operations['settings'] = array(
    'type' => 'group',
    'class' => array('operations-settings'),
    'title' => t('Settings'),
    'children' => array(),
  );

  $settings = &$operations['settings']['children'];

  $settings['basic'] = array(
    'title' => t('Basic'),
    'description' => t('Edit name, path and other basic settings for the page.'),
    'form' => 'page_manager_page_form_basic',
  );

  $arguments = page_manager_page_get_named_arguments($page->path);
  if ($arguments) {
    $settings['argument'] = array(
      'title' => t('Arguments'),
      'description' => t('Set up contexts for the arguments on this page.'),
      'form' => 'page_manager_page_form_argument',
    );
  }

  $settings['access'] = array(
    'title' => t('Access'),
    'description' => t('Control what users can access this page.'),
    'admin description' => t('Access rules are used to test if the page is accessible and any menu items associated with it are visible.'),
    'form' => 'page_manager_page_form_access',
  );

  $settings['menu'] = array(
    'title' => t('Menu'),
    'description' => t('Provide this page a visible menu or a menu tab.'),
    'form' => 'page_manager_page_form_menu',
  );

  $operations['actions']['children']['clone'] = array(
    'title' => t('Clone'),
    'description' => t('Make a copy of this page'),
    'form' => 'page_manager_page_form_clone',
  );
  $operations['actions']['children']['export'] = array(
    'title' => t('Export'),
    'description' => t('Export this page as code that can be imported or embedded into a module.'),
    'form' => 'page_manager_page_form_export',
  );
  if ($page->export_type == (EXPORT_IN_CODE | EXPORT_IN_DATABASE)) {
    $operations['actions']['children']['delete'] = array(
      'title' => t('Revert'),
      'description' => t('Remove all changes to this page and revert to the version in code.'),
      'form' => 'page_manager_page_form_delete',
    );
  }
  else if ($page->export_type != EXPORT_IN_CODE) {
    $operations['actions']['children']['delete'] = array(
      'title' => t('Delete'),
      'description' => t('Remove this page from your system completely.'),
      'form' => 'page_manager_page_form_delete',
    );
  }

  $subtask = array(
    'name' => $page->name,
    'admin title' => check_plain($page->admin_title),
    'admin description' => filter_xss_admin($page->admin_description),
    'admin summary' => 'page_manager_page_admin_summary',
    'admin path' => $page->path,
    'admin type' => t('Custom'),
    'subtask' => $page,
    'operations' => $operations,
    'operations include' => array(
      'file' => 'page.admin.inc',
      'path' => drupal_get_path('module', 'page_manager') . '/plugins/tasks',
    ),
    'single task' => empty($page->multiple),
    'row class' => empty($page->disabled) ? 'page-manager-enabled' : 'page-manager-disabled',
    'storage' => $page->type == t('Default') ? t('In code') : $page->type,
    'disabled' => !empty($page->disabled),
    // This works for both enable AND disable
    'enable callback' => 'page_manager_page_enable',
  );

  // default handlers may appear from a default subtask.
  if (isset($page->default_handlers)) {
    $subtask['default handlers'] = $page->default_handlers;
  }
  return $subtask;
}

/**
 * Delegated implementation of hook_theme().
 */
function page_manager_page_theme(&$items, $task) {
  $base = array(
    'file' => 'page.admin.inc',
    'path' => drupal_get_path('module', 'page_manager') . '/plugins/tasks',
  );
  $items['page_manager_page_form_argument_table'] = $base + array(
    'render element' => 'form',
  );
  $items['page_manager_page_lock'] = $base + array(
    'variables' => array('lock' => array(), 'task_name' => NULL),
  );
  $items['page_manager_page_changed'] = $base + array(
    'variables' => array(),
  );
}

// --------------------------------------------------------------------------
// Page execution functions

/**
 * Execute a page task.
 *
 * This is the callback to entries in the Drupal menu system created by the
 * page task.
 *
 * @param $subtask_id
 *   The name of the page task used.
 * @param ...
 *   A number of context objects as specified by the user when
 *   creating named arguments in the path.
 */
function page_manager_page_execute($subtask_id) {
  $page = page_manager_page_load($subtask_id);
  $task = page_manager_get_task($page->task);
  $subtask = page_manager_get_task_subtask($task, $subtask_id);

  // Turn the contexts into a properly keyed array.
  $contexts = array();
  $args = array();
  foreach (func_get_args() as $count => $arg) {
    if (is_object($arg) && get_class($arg) == 'ctools_context') {
      $contexts[$arg->id] = $arg;
      $args[] = $arg->original_argument;
    }
    else if ($count) {
      $args[] = $arg;
    }
  }

  $count = 0;
  $names = page_manager_page_get_named_arguments($page->path);
  $bits = explode('/', $page->path);

  if ($page->arguments) {
    foreach ($page->arguments as $name => $argument) {
      // Optional arguments must be converted to contexts too, if they exist.
      if ($bits[$names[$name]][0] == '!') {
        ctools_include('context');
        $argument['keyword'] = $name;
        if (isset($args[$count])) {
          // Hack: use a special argument config variable to learn if we need
          $plugin = ctools_get_argument($argument['name']);
          // to use menu_tail style behavior:
          if (empty($argument['settings']['use_tail'])) {
            $value = $args[$count];
          }
          else {
            $value = implode('/', array_slice($args, $count));
          }

          $context = ctools_context_get_context_from_argument($argument, $value);
        }
        else {
          // make sure there is a placeholder context for missing optional contexts.
          $context = ctools_context_get_context_from_argument($argument, NULL, TRUE);
          // Force the title to blank for replacements
        }
        if ($context) {
          $contexts[$context->id] = $context;
        }
      }
      $count++;
    }
  }

  if ($function = ctools_plugin_get_function($task, 'page callback')) {
    return call_user_func_array($function, array($page, $contexts, $args));
  }

  ctools_include('context-task-handler');
  $output = ctools_context_handler_render($task, $subtask, $contexts, $args);
  if ($output === FALSE) {
    return drupal_not_found();
  }

  return $output;
}

// --------------------------------------------------------------------------
// Context type callbacks

/**
 * Return a list of arguments used by this task.
 */
function page_manager_page_get_arguments($task, $subtask) {
  return _page_manager_page_get_arguments($subtask['subtask']);
}

function _page_manager_page_get_arguments($page) {
  $arguments = array();
  if (!empty($page->arguments)) {
    foreach ($page->arguments as $keyword => $argument) {
      if (isset($argument['name'])) {
        $argument['keyword'] = $keyword;
        $arguments[$keyword] = $argument;
      }
    }
  }
  return $arguments;
}

/**
 * Get a group of context placeholders for the arguments.
 */
function page_manager_page_get_contexts($task, $subtask) {
  ctools_include('context');
  return ctools_context_get_placeholders_from_argument(page_manager_page_get_arguments($task, $subtask));
}

/**
 * Return a list of arguments used by this task.
 */
function page_manager_page_access_restrictions($task, $subtask, $contexts) {
  $page = $subtask['subtask'];
  return ctools_access_add_restrictions($page->access, $contexts);
}

// --------------------------------------------------------------------------
// Page task database info.

/**
 * Create a new page with defaults appropriately set from schema.
 */
function page_manager_page_new() {
  ctools_include('export');
  return ctools_export_new_object('page_manager_pages');
}

/**
 * Load a single page subtask.
 */
function page_manager_page_load($name) {
  ctools_include('export');
  $result = ctools_export_load_object('page_manager_pages', 'names', array($name));
  if (isset($result[$name])) {
    return $result[$name];
  }
}

/**
 * Load all page subtasks.
 */
function page_manager_page_load_all($task = NULL) {
  ctools_include('export');

  if (empty($task)) {
    return ctools_export_load_object('page_manager_pages');
  }
  else {
    return ctools_export_load_object('page_manager_pages', 'conditions', array('task' => $task));
  }
}

/**
 * Write a page subtask to the database.
 */
function page_manager_page_save(&$page) {
  $update = (isset($page->pid)) ? array('pid') : array();
  $task = page_manager_get_task($page->task);

  if ($function = ctools_plugin_get_function($task, 'save')) {
    $function($page, $update);
  }
  drupal_write_record('page_manager_pages', $page, $update);

  // If this was a default page we may need to write default task
  // handlers that we provided as well.
  if (!$update && isset($page->default_handlers)) {
    $handlers = page_manager_load_task_handlers(page_manager_get_task('page'), $page->name);
    foreach ($page->default_handlers as $name => $handler) {
      if (!isset($handlers[$name]) || !($handlers[$name]->export_type & EXPORT_IN_DATABASE)) {
        // Make sure this is right, as exports can wander a bit.
        $handler->subtask = $page->name;
        page_manager_save_task_handler($handler);
      }
    }
  }
  return $page;
}

/**
 * Remove a page subtask.
 */
function page_manager_page_delete($page) {
  $task = page_manager_get_task($page->task);
  if ($function = ctools_plugin_get_function($task, 'delete')) {
    $function($page);
  }
  if (!empty($task['uses handlers'])) {
    $handlers = page_manager_load_task_handlers($task, $page->name);
    foreach ($handlers as $handler) {
      page_manager_delete_task_handler($handler);
    }
  }
  db_delete('page_manager_pages')
    ->condition('name', $page->name)
    ->execute();
  // Make sure that the cache is reset so that the menu rebuild does not
  // rebuild this page again.
  ctools_include('export');
  ctools_export_load_object_reset('page_manager_pages');
  menu_rebuild();
}

/**
 * Export a page subtask.
 */
function page_manager_page_export($page, $with_handlers = FALSE, $indent = '') {
  $task = page_manager_get_task($page->task);
  $append = '';

  if ($function = ctools_plugin_get_function($task, 'export')) {
    $append = $function($page, $indent);
  }

  ctools_include('export');
  $output = ctools_export_object('page_manager_pages', $page, $indent);
  $output .= $append;

  if ($with_handlers) {
    if (is_array($with_handlers)) {
      $handlers = $with_handlers;
    }
    else {
      $handlers = page_manager_load_task_handlers(page_manager_get_task('page'), $page->name);
    }
    $output .= $indent . '$page->default_handlers = array();' . "\n";
    foreach ($handlers as $handler) {
      $output .= page_manager_export_task_handler($handler, $indent);
      $output .= $indent . '$page->default_handlers[$handler->name] = $handler;' . "\n";
    }
  }
  return $output;
}

/**
 * Get a list of named arguments in a page manager path.
 *
 * @param $path
 *   A normal Drupal path.
 *
 * @return
 *   An array of % marked variable arguments, keyed by the argument's name.
 *   The value will be the position of the argument so that it can easily
 *   be found. Items with a position of -1 have multiple positions.
 */
function page_manager_page_get_named_arguments($path) {
  $arguments = array();
  $bits = explode('/', $path);
  foreach ($bits as $position => $bit) {
    if ($bit && ($bit[0] == '%' || $bit[0] == '!')) {
      // special handling for duplicate path items and substr to remove the %
      $arguments[substr($bit, 1)] = isset($arguments[$bit]) ? -1 : $position;
    }
  }

  return $arguments;
}

/**
 * Load a context from an argument for a given page task.
 *
 * Helper function for pm_arg_load(), which is in page_manager.module because
 * drupal's menu system does not allow loader functions to reside in separate
 * files.
 *
 * @param $value
 *   The incoming argument value.
 * @param $subtask
 *   The subtask id.
 * @param $argument
 *   The numeric position of the argument in the path, counting from 0.
 *
 * @return
 *   A context item if one is configured, the argument if one is not, or
 *   FALSE if restricted or invalid.
 */
function _pm_arg_load($value, $subtask, $argument) {
  $page = page_manager_page_load($subtask);
  if (!$page) {
    return FALSE;
  }

  $path = explode('/', $page->path);
  if (empty($path[$argument])) {
    return FALSE;
  }

  $keyword = substr($path[$argument], 1);
  if (empty($page->arguments[$keyword])) {
    return $value;
  }

  $page->arguments[$keyword]['keyword'] = $keyword;

  ctools_include('context');
  $context = ctools_context_get_context_from_argument($page->arguments[$keyword], $value);

  // convert false equivalents to false.
  return $context ? $context : FALSE;
}

/**
 * Provide a nice administrative summary of the page so an admin can see at a
 * glance what this page does and how it is configured.
 */
function page_manager_page_admin_summary($task, $subtask) {
  $task_name = page_manager_make_task_name($task['name'], $subtask['name']);
  $page = $subtask['subtask'];
  $output = '';

  $rows = array();

  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Storage')),
    array('class' => array('page-summary-data'), 'data' => $subtask['storage']),
    array('class' => array('page-summary-operation'), 'data' => ''),
  );

  if (!empty($page->disabled)) {
    $link = l(t('Enable'), page_manager_edit_url($task_name, array('handlers', $page->name, 'actions', 'enable')));
    $text = t('Disabled');
  }
  else {
    $link = l(t('Disable'), page_manager_edit_url($task_name, array('handlers', $page->name, 'actions', 'disable')));
    $text = t('Enabled');
  }

  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Status')),
    array('class' => array('page-summary-data'), 'data' => $text),
    array('class' => array('page-summary-operation'), 'data' => $link),
  );


  $path = array();
  foreach (explode('/', $page->path) as $bit) {
    if ($bit[0] != '!') {
      $path[] = $bit;
    }
  }

  $path = implode('/', $path);
  $front = variable_get('site_frontpage', 'node');

  $link = l(t('Edit'), page_manager_edit_url($task_name, array('settings', 'basic')));
  $message = '';
  if ($path == $front) {
    $message = t('This is your site home page.');
  }
  else if (!empty($page->make_frontpage)) {
    $message = t('This page is set to become your site home page.');
  }

  if ($message) {
    $rows[] = array(
      array('class' => array('page-summary-data'), 'data' => $message, 'colspan' => 2),
      array('class' => array('page-summary-operation'), 'data' => $link),
    );
  }

  if (strpos($path, '%') === FALSE) {
    $path = l('/' . $page->path, $path);
  }
  else {
    $path = '/' . $page->path;
  }

  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Path')),
    array('class' => array('page-summary-data'), 'data' => $path),
    array('class' => array('page-summary-operation'), 'data' => $link),
  );

  if (empty($access['plugins'])) {
    $access['plugins'] = array();
  }

  $contexts = page_manager_page_get_contexts($task, $subtask);
  $access = ctools_access_group_summary($page->access, $contexts);
  if ($access) {
    $access = t('Accessible only if @conditions.', array('@conditions' => $access));
  }
  else {
    $access = t('This page is publicly accessible.');
  }

  $link = l(t('Edit'), page_manager_edit_url($task_name, array('settings', 'access')));

  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Access')),
    array('class' => array('page-summary-data'), 'data' => $access),
    array('class' => array('page-summary-operation'), 'data' => $link),
  );

  $menu_options = array(
    'none' => t('No menu entry.'),
    'normal' => t('Normal menu entry.'),
    'tab' => t('Menu tab.'),
    'default tab' => t('Default menu tab.'),
    'action' => t('Local action'),
  );

  if (!empty($page->menu)) {
    $menu = $menu_options[$page->menu['type']];
    if ($page->menu['type'] != 'none') {
      $menu .= ' ' . t('Title: %title.', array('%title' => $page->menu['title']));
      switch ($page->menu['type']) {
        case 'default tab':
          $menu .= ' ' . t('Parent title: %title.', array('%title' => $page->menu['parent']['title']));
          break;
        case 'normal':
          if (module_exists('menu')) {
            $menus = menu_get_menus();
            $menu .= ' ' . t('Menu block: %title.', array('%title' => $menus[$page->menu['name']]));
          }
          break;
      }
    }
  }
  else {
    $menu = t('No menu entry');
  }

  $link = l(t('Edit'), page_manager_edit_url($task_name, array('settings', 'menu')));
  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Menu')),
    array('class' => array('page-summary-data'), 'data' => $menu),
    array('class' => array('page-summary-operation'), 'data' => $link),
  );

  $output .= theme('table', array('rows' => $rows, 'attributes' => array('id' => 'page-manager-page-summary')));
  return $output;
}

/**
 * Callback to enable/disable the page from the UI.
 */
function page_manager_page_enable(&$cache, $status) {
  $page = &$cache->subtask['subtask'];
  ctools_include('export');
  ctools_export_set_object_status($page, $status);

  $page->disabled = FALSE;
}

/**
 * Recalculate the arguments when something like the path changes.
 */
function page_manager_page_recalculate_arguments(&$page) {
  // Ensure $page->arguments contains only real arguments:
  $arguments = page_manager_page_get_named_arguments($page->path);
  $args = array();
  foreach ($arguments as $keyword => $position) {
    if (isset($page->arguments[$keyword])) {
      $args[$keyword] = $page->arguments[$keyword];
    }
    else {
      $args[$keyword] = array(
        'id' => '',
        'identifier' => '',
        'argument' => '',
        'settings' => array(),
      );
    }
  }
  $page->arguments = $args;
}

/**
 * When adding or cloning a new page, this creates a new page cache
 * and adds our page to it.
 *
 * This does not check to see if the existing cache is already locked.
 * This must be done beforehand.
 *
 * @param &$page
 *   The page to create.
 * @param &$cache
 *   The cache to use. If the cache has any existing task handlers,
 *   they will be marked for deletion. This may be a blank object.
 */
function page_manager_page_new_page_cache(&$page, &$cache) {
  // Does a page already exist? If so, we are overwriting it so
  // take its pid.
  if (!empty($cache->subtask) && !empty($cache->subtask['subtask']) && !empty($cache->subtask['subtask']->pid)) {
    $page->pid = $cache->subtask['subtask']->pid;
  }
  else {
    $cache->new = TRUE;
  }

  $cache->task_name = page_manager_make_task_name('page', $page->name);
  $cache->task_id = 'page';
  $cache->task = page_manager_get_task('page');
  $cache->subtask_id = $page->name;
  $page->export_type = EXPORT_IN_DATABASE;
  $page->type = t('Normal');
  $cache->subtask = page_manager_page_build_subtask($cache->task, $page);

  if (isset($cache->handlers)) {
    foreach($cache->handlers as $id => $handler) {
      $cache->handler_info[$id]['changed'] = PAGE_MANAGER_CHANGED_DELETED;
    }
  }
  else {
    $cache->handlers = array();
    $cache->handler_info = array();
  }

  if (!empty($page->default_handlers)) {
    foreach ($page->default_handlers as $id => $handler) {
      page_manager_handler_add_to_page($cache, $handler);
    }
  }

  $cache->locked = FALSE;
  $cache->changed = TRUE;
}
