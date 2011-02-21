<?php
// $Id: search.inc,v 1.4 2010/10/11 22:18:23 sdboyer Exp $

/**
 * @file
 * Handle the 'node view' override task.
 *
 * This plugin overrides node/%node and reroutes it to the page manager, where
 * a list of tasks can be used to service this request based upon criteria
 * supplied by access plugins.
 */

/**
 * Specialized implementation of hook_page_manager_task_tasks(). See api-task.html for
 * more information.
 */
function page_manager_search_page_manager_tasks() {
  if (!module_exists('search')) {
    return;
  }

  return array(
    // This is a 'page' task and will fall under the page admin UI
    'task type' => 'page',
    'title' => t('Search'),

    // There are multiple search pages, let's override each of them
    // separately.
    'subtasks' => TRUE,
    'subtask callback' => 'page_manager_search_subtask',
    'subtasks callback' => 'page_manager_search_subtasks',

    // Menu hooks so that we can alter the node/%node menu entry to point to us.
    'hook menu alter' => 'page_manager_search_menu_alter',

    // This is task uses 'context' handlers and must implement these to give the
    // handler data it needs.
    'handler type' => 'context',
    'get arguments' => 'page_manager_search_get_arguments',
    'get context placeholders' => 'page_manager_search_get_contexts',

  );
}

/**
 * Callback defined by page_manager_search_page_manager_tasks().
 *
 * Alter the search tabs to work with page manager. The search flow is
 * quite odd, and tracing through the code takes hours to realize
 * that the tab you click on does not normally actually handle
 * the search. This tries to account for that.
 *
 * Note to module authors: This tends to work a lot better with modules
 * that override their own search pages if their _alter runs *before*
 * this one.
 */
function page_manager_search_menu_alter(&$items, $task) {
  // We are creating two sets of tabs. One set is for searching without
  // keywords. A second set is for searching *with* keywords. This
  // is necessary because search/node/% and search/node need to be
  // different due to the way the search menu items function.

  // Go through each search module item.
  foreach (module_implements('search') as $name) {
    // Do not bother with search menu items that should not have search tabs.
    if (!module_invoke($name, 'search', 'name')) {
      continue;
    }

    // Put these items under the default search tab which is node.
    $items["search/$name/%menu_tail"]['tab_parent'] = "search/node/%menu_tail";
    $items["search/$name/%menu_tail"]['tab_root'] = "search/node/%menu_tail";

    $callback = $items["search/$name/%menu_tail"]['page callback'];

    // Even if a search page is not implemented, we need to add an extra
    // entry anyway, for two reasons.
    //
    // 1) The 'search' menu entry actually handles all entries by default
    // and that is going to be bad if the node search is overridden and
    // 2) We need to have dual entries to make sure that the tabs are right.
    if (variable_get('page_manager_search_disabled_' . $name, TRUE) || ($callback != 'search_view' && !variable_get('page_manager_override_anyway', FALSE))) {
      $items["search/$name"] = $items["search/$name/%menu_tail"];

      // Put these items under the real search tab.
      $items["search/$name"]['tab_parent'] = 'search';
      $items["search/$name"]['tab_root'] = 'search';

      if ($name == 'node') {
        $items["search/$name"]['type'] = MENU_DEFAULT_LOCAL_TASK;
        // The default tab should always be left weighted. Because of the way
        // menu sorts, this item tends to float around if not weighted.
        $items["search/$name"]['weight'] = -10;
        $items["search/$name/%menu_tail"]['weight'] = -10;
      }

      if ($callback == 'search_view' || variable_get('page_manager_override_anyway', FALSE)) {
        $items["search/$name/%menu_tail"]['page callback'] = 'page_manager_search_view';
        $items["search/$name/%menu_tail"]['file path'] = $task['path'];
        $items["search/$name/%menu_tail"]['file'] = $task['file'];
      }

      continue;
    }

    if ($callback == 'search_view' || variable_get('page_manager_override_anyway', FALSE)) {
      $items["search/$name/%menu_tail"]['page callback'] = 'page_manager_search_page';
      $items["search/$name/%menu_tail"]['file path'] = $task['path'];
      $items["search/$name/%menu_tail"]['file'] = $task['file'];

      // Add a version that doesn't contain the menu tail for the no keywords
      // version. Ordinarily this works because the top level 'search' just
      // passes through.
      $items["search/$name"] = $items["search/$name/%menu_tail"];
      $items["search/$name/%menu_tail"]['page arguments'] = array(1, 2);

      // Put these items under the real search tab.
      $items["search/$name"]['tab_parent'] = 'search';
      $items["search/$name"]['tab_root'] = 'search';

      // Content search is the default search link, so we have to override
      // the default task as well.
      if ($name == 'node') {
        $items["search/$name"]['type'] = MENU_DEFAULT_LOCAL_TASK;
        // The default tab should always be left weighted. Because of the way
        // menu sorts, this item tends to float around if not weighted.
        $items["search/$name"]['weight'] = -10;
        $items["search/$name/%menu_tail"]['weight'] = -10;

        $items["search"]['page callback'] = 'page_manager_search_page';
        $items["search"]['page arguments'] = array('node');
        $items["search"]['file path'] = $task['path'];
        $items["search"]['file'] = $task['file'];
      }
    }
    else {
      // automatically disable this task if it cannot be enabled.
      variable_set('page_manager_search_disabled_' . $name, TRUE);
      if (!empty($GLOBALS['page_manager_enabling_search'])) {
        drupal_set_message(t('Page manager module is unable to enable search/@name/%menu_tail because some other module already has overridden with %callback.', array('%callback' => $callback, '@name' => $name)), 'error');
      }
    }
  }
}

/**
 * Replacement function for normal search view.
 *
 * This function resets the active trail because menu system loses track
 * of it due to the special way we're handling search items.
 */
function page_manager_search_view($type = 'node') {
  // @TODO: test that this actually works once page_manager is upgraded.
  drupal_static_reset('menu_set_active_trail');
  drupal_static_reset('menu_tree_page_data');
  menu_set_active_trail();

  module_load_include('inc', 'search', 'search.pages');
  return search_view($type);
}

/**
 * Entry point for our overridden node view.
 *
 * This function asks its assigned handlers who, if anyone, would like
 * to run with it. If no one does, it passes through to Drupal core's
 * node view, which is node_page_view().
 */
function page_manager_search_page($type) {
  ctools_include('menu');
  menu_set_active_trail(ctools_get_menu_trail('search/' . $type));

  // Get the arguments and construct a keys string out of them.
  $args = func_get_args();

  // We have to remove the $type.
  array_shift($args);

  // And implode() it all back together.
  $keys = $args ? implode('/', $args) : '';

  // Load my task plugin
  $task = page_manager_get_task('search');
  $subtask = page_manager_get_task_subtask($task, $type);

  // Load the node into a context.
  ctools_include('context');
  ctools_include('context-task-handler');
  $contexts = ctools_context_handler_get_task_contexts($task, $subtask, array($keys));

  $output = ctools_context_handler_render($task, $subtask, $contexts, array($keys));
  if ($output !== FALSE) {
    return $output;
  }

  $function = 'search_view';
  foreach (module_implements('page_manager_override') as $module) {
    $call = $module . '_page_manager_override';
    if (($rc = $call('search')) && function_exists($rc)) {
      $function = $rc;
      break;
    }
  }

  // Otherwise, fall back.

  // Put the $type back on the arguments.
  module_load_include('inc', 'search', 'search.pages');
  array_unshift($args, $type);
  return call_user_func_array($function, $args);
}

/**
 * Callback to get arguments provided by this task handler.
 *
 * Since this is the node view and there is no UI on the arguments, we
 * create dummy arguments that contain the needed data.
 */
function page_manager_search_get_arguments($task, $subtask_id) {
  return array(
    array(
      'keyword' => 'keywords',
      'identifier' => t('Keywords'),
      'id' => 1,
      'name' => 'string',
      'settings' => array('use_tail' => TRUE),
    ),
  );
}

/**
 * Callback to get context placeholders provided by this handler.
 */
function page_manager_search_get_contexts($task, $subtask_id) {
  return ctools_context_get_placeholders_from_argument(page_manager_search_get_arguments($task, $subtask_id));
}

/**
 * Callback to enable/disable the page from the UI.
 */
function page_manager_search_enable($cache, $status) {
  variable_set('page_manager_search_disabled_' . $cache->subtask_id, $status);

  // Set a global flag so that the menu routine knows it needs
  // to set a message if enabling cannot be done.
  if (!$status) {
    $GLOBALS['page_manager_enabling_search'] = TRUE;
  }
}

/**
 * Task callback to get all subtasks.
 *
 * Return a list of all subtasks.
 */
function page_manager_search_subtasks($task) {
  $return = array();
  foreach (module_implements('search') as $name) {
    if(module_invoke($name, 'search', 'name')) {
      $return[$name] = page_manager_search_build_subtask($task, $name);
    }
  }

  return $return;
}

/**
 * Callback to return a single subtask.
 */
function page_manager_search_subtask($task, $subtask_id) {
  return page_manager_search_build_subtask($task, $subtask_id);
}

/**
 * Build a subtask array for a given page.
 */
function page_manager_search_build_subtask($task, $name) {
  $type = module_invoke($name, 'search', 'name', TRUE);
  $subtask = array(
    'name' => $name,
    'admin title' => $type,
    'admin path' => "search/$name/!keywords",
    'admin description' => t('Search @type', array('@type' => $type)),
    'admin type' => t('System'),
    'row class' => empty($page->disabled) ? 'page-manager-enabled' : 'page-manager-disabled',
    'storage' => t('In code'),
    'disabled' => variable_get('page_manager_search_disabled_' . $name, TRUE),
    // This works for both enable AND disable
    'enable callback' => 'page_manager_search_enable',
  );

  return $subtask;
}
