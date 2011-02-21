<?php
// $Id: user_view.inc,v 1.7 2011/01/06 00:06:18 merlinofchaos Exp $

/**
 * @file
 * Overrides the user profile display at user/%user.
 *
 * Specialized implementation of hook_page_manager_task_tasks(). See api-task.html for
 * more information.
 */
function page_manager_user_view_page_manager_tasks() {
  return array(
    // This is a 'page' task and will fall under the page admin UI
    'task type' => 'page',
    'title' => t('User profile template'),
    'admin title' => t('User profile template'),
    'admin description' => t('When enabled, this overrides the default Drupal behavior for displaying user profiles at <em>user/%user</em>. If you add variants, you may use selection criteria such as roles or user access to provide different views of user profiles. If no variant is selected, the default Drupal user view will be used. Please note that if you are using pathauto, aliases may make a node to be somewhere else, but as far as Drupal is concerned, they are still at user/%user.'),
    'admin path' => 'user/%user',

    // Callback to add items to the page managertask administration form:
    'task admin' => 'page_manager_user_view_task_admin',

    'hook menu' => 'page_manager_user_view_menu',
    'hook menu alter' => 'page_manager_user_view_menu_alter',

    // This is task uses 'context' handlers and must implement these to give the
    // handler data it needs.
    'handler type' => 'context', // handler type -- misnamed
    'get arguments' => 'page_manager_user_view_get_arguments',
    'get context placeholders' => 'page_manager_user_view_get_contexts',

    // Allow this to be enabled or disabled:
    'disabled' => variable_get('page_manager_user_view_disabled', TRUE),
    'enable callback' => 'page_manager_user_view_enable',
  );
}

/**
 * Callback defined by page_manager_user_view_page_manager_tasks().
 *
 * Alter the user view input so that user view comes to us rather than the
 * normal user view process.
 */
function page_manager_user_view_menu_alter(&$items, $task) {
  if (variable_get('page_manager_user_view_disabled', TRUE)) {
    return;
  }

  // Override the user view handler for our purpose.
  if ($items['user/%user']['page callback'] == 'user_view_page' || variable_get('page_manager_override_anyway', FALSE)) {
    $items['user/%user']['page callback'] = 'page_manager_user_view_page';
    $items['user/%user']['file path'] = $task['path'];
    $items['user/%user']['file'] = $task['file'];
  }
  else {
    // automatically disable this task if it cannot be enabled.
    variable_set('page_manager_user_view_disabled', TRUE);
    if (!empty($GLOBALS['page_manager_enabling_user_view'])) {
      drupal_set_message(t('Page manager module is unable to enable user/%user because some other module already has overridden with %callback.', array('%callback' => $items['user/%user']['page callback'])), 'error');
    }
  }
}

/**
 * Entry point for our overridden user view.
 *
 * This function asks its assigned handlers who, if anyone, would like
 * to run with it. If no one does, it passes through to Drupal core's
 * user view, which is user_page_view().
 */
function page_manager_user_view_page($account) {
  // Load my task plugin:
  $task = page_manager_get_task('user_view');

  // Load the account into a context.
  ctools_include('context');
  ctools_include('context-task-handler');
  $contexts = ctools_context_handler_get_task_contexts($task, '', array($account));

  // Build content. @todo -- this may not be right.
  user_build_content($account);

  $output = ctools_context_handler_render($task, '', $contexts, array($account->uid));
  if ($output === FALSE) {
    // Fall back!
    module_load_include('inc', 'user', 'user.pages');
    $output = user_view($account);
  }
  else {
    //fire off "view" op so that triggers still work
//    user_module_invoke('view', $array = array(), $account);
  }
  return $output;
}

/**
 * Callback to get arguments provided by this task handler.
 *
 * Since this is the node view and there is no UI on the arguments, we
 * create dummy arguments that contain the needed data.
 */
function page_manager_user_view_get_arguments($task, $subtask_id) {
  return array(
    array(
      'keyword' => 'user',
      'identifier' => t('User being viewed'),
      'id' => 1,
      'name' => 'uid',
      'settings' => array(),
    ),
  );
}

/**
 * Callback to get context placeholders provided by this handler.
 */
function page_manager_user_view_get_contexts($task, $subtask_id) {
  return ctools_context_get_placeholders_from_argument(page_manager_user_view_get_arguments($task, $subtask_id));
}

/**
 * Callback to enable/disable the page from the UI.
 */
function page_manager_user_view_enable($cache, $status) {
  variable_set('page_manager_user_view_disabled', $status);

  // Set a global flag so that the menu routine knows it needs
  // to set a message if enabling cannot be done.
  if (!$status) {
    $GLOBALS['page_manager_enabling_user_view'] = TRUE;
  }
}
