<?php
// $Id: blog_user.inc,v 1.3 2009/10/13 18:12:09 merlinofchaos Exp $

/**
 * Specialized implementation of hook_page_manager_task_tasks(). See api-task.html for
 * more information.
 */
function page_manager_blog_user_page_manager_tasks() {
  if (!module_exists('blog')) {
    return;
  }

  return array(
    // This is a 'page' task and will fall under the page admin UI
    'task type' => 'page',
    'title' => t('User blog'),
    'admin title' => t('User blog'),
    'admin description' => t('When enabled, this overrides the default Drupal behavior for displaying user blogs at <em>blog/%user</em>. If no variant is selected, the default Drupal user blog will be used.'),
    'admin path' => 'blog/%user',

    // Callback to add items to the page managertask administration form:
    'task admin' => 'page_manager_blog_user_task_admin',

    'hook menu alter' => 'page_manager_blog_user_menu_alter',

    // This is task uses 'context' handlers and must implement these to give the
    // handler data it needs.
    'handler type' => 'context', // handler type -- misnamed
    'get arguments' => 'page_manager_blog_user_get_arguments',
    'get context placeholders' => 'page_manager_blog_user_get_contexts',

    // Allow this to be enabled or disabled:
    'disabled' => variable_get('page_manager_blog_user_disabled', TRUE),
    'enable callback' => 'page_manager_blog_user_enable',
  );
}

/**
 * Callback defined by page_manager_blog_user_page_manager_tasks().
 *
 * Alter the user view input so that user view comes to us rather than the
 * normal user view process.
 */
function page_manager_blog_user_menu_alter(&$items, $task) {
  if (variable_get('page_manager_blog_user_disabled', TRUE)) {
    return;
  }

  // Override the user view handler for our purpose.
  if ($items['blog/%user_uid_optional']['page callback'] == 'blog_page_user' || variable_get('page_manager_override_anyway', FALSE)) {
    $items['blog/%user_uid_optional']['page callback'] = 'page_manager_blog_user';
    $items['blog/%user_uid_optional']['file path'] = $task['path'];
    $items['blog/%user_uid_optional']['file'] = $task['file'];
  }
  else {
    // automatically disable this task if it cannot be enabled.
    variable_set('page_manager_blog_user_disabled', TRUE);
    if (!empty($GLOBALS['page_manager_enabling_blog_user'])) {
      drupal_set_message(t('Page manager module is unable to enable blog/%user because some other module already has overridden with %callback.', array('%callback' => $items['blog/%user']['page callback'])), 'error');
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
function page_manager_blog_user($account) {
  // Load my task plugin:
  $task = page_manager_get_task('blog_user');

  // Load the account into a context.
  ctools_include('context');
  ctools_include('context-task-handler');
  $contexts = ctools_context_handler_get_task_contexts($task, '', array($account));

  $output = ctools_context_handler_render($task, '', $contexts, array($account->uid));
  if ($output !== FALSE) {
    return $output;
  }

  module_load_include('inc', 'blog', 'blog.pages');
  $function = 'blog_page_user';
  foreach (module_implements('page_manager_override') as $module) {
    $call = $module . '_page_manager_override';
    if (($rc = $call('blog_user')) && function_exists($rc)) {
      $function = $rc;
      break;
    }
  }

  // Otherwise, fall back.
  return $function($account);
}

/**
 * Callback to get arguments provided by this task handler.
 *
 * Since this is the node view and there is no UI on the arguments, we
 * create dummy arguments that contain the needed data.
 */
function page_manager_blog_user_get_arguments($task, $subtask_id) {
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
function page_manager_blog_user_get_contexts($task, $subtask_id) {
  return ctools_context_get_placeholders_from_argument(page_manager_blog_user_get_arguments($task, $subtask_id));
}

/**
 * Callback to enable/disable the page from the UI.
 */
function page_manager_blog_user_enable($cache, $status) {
  variable_set('page_manager_blog_user_disabled', $status);

  // Set a global flag so that the menu routine knows it needs
  // to set a message if enabling cannot be done.
  if (!$status) {
    $GLOBALS['page_manager_enabling_blog_user'] = TRUE;
  }
}
