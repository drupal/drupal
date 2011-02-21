<?php
// $Id: blog.inc,v 1.3 2010/01/29 21:21:20 merlinofchaos Exp $

/**
 * Specialized implementation of hook_page_manager_task_tasks(). See api-task.html for
 * more information.
 */
function page_manager_blog_page_manager_tasks() {
  if (!module_exists('blog')) {
    return;
  }

  return array(
    // This is a 'page' task and will fall under the page admin UI
    'task type' => 'page',

    'title' => t('All blogs'),
    'admin title' => t('All blogs'),
    'admin description' => t('When enabled, this overrides the default Drupal behavior for the all blogs at <em>/blog</em>. If no variant is selected, the default Drupal most recent blog posts will be shown.'),
    'admin path' => 'blog',

    // Menu hooks so that we can alter the node/%node menu entry to point to us.
    'hook menu alter' => 'page_manager_blog_menu_alter',

    // This is task uses 'context' handlers and must implement these to give the
    // handler data it needs.
    'handler type' => 'context',

    // Allow this to be enabled or disabled:
    'disabled' => variable_get('page_manager_blog_disabled', TRUE),
    'enable callback' => 'page_manager_blog_enable',
  );
}

/**
 * Callback defined by page_manager_blog_page_manager_tasks().
 *
 * Alter the node edit input so that node edit comes to us rather than the
 * normal node edit process.
 */
function page_manager_blog_menu_alter(&$items, $task) {
  if (variable_get('page_manager_blog_disabled', TRUE)) {
    return;
  }

  $callback = $items['blog']['page callback'];
  // Override the node edit handler for our purpose.
  if ($callback == 'blog_page_last' || variable_get('page_manager_override_anyway', FALSE)) {
    $items['blog']['page callback'] = 'page_manager_blog';
    $items['blog']['file path'] = $task['path'];
    $items['blog']['file'] = $task['file'];
  }
  else {
    variable_set('page_manager_blog_disabled', TRUE);
    if (!empty($GLOBALS['page_manager_enabling_blog'])) {
      drupal_set_message(t('Page manager module is unable to enable blog because some other module already has overridden with %callback.', array('%callback' => $callback)), 'warning');
    }
    return;
  }

}

/**
 * Entry point for our overridden node edit.
 *
 * This function asks its assigned handlers who, if anyone, would like
 * to run with it. If no one does, it passes through to Drupal core's
 * node edit, which is node_page_edit().
 */
function page_manager_blog() {
  // Load my task plugin
  $task = page_manager_get_task('blog');

  ctools_include('context');
  ctools_include('context-task-handler');
  $output = ctools_context_handler_render($task, '', array(), array());
  if ($output !== FALSE) {
    return $output;
  }

  module_load_include('inc', 'blog', 'blog.pages');
  $function = 'blog_page_last';
  foreach (module_implements('page_manager_override') as $module) {
    $call = $module . '_page_manager_override';
    if (($rc = $call('blog')) && function_exists($rc)) {
      $function = $rc;
      break;
    }
  }

  // Otherwise, fall back.
  return $function();
}

/**
 * Callback to enable/disable the page from the UI.
 */
function page_manager_blog_enable($cache, $status) {
  variable_set('page_manager_blog_disabled', $status);
  // Set a global flag so that the menu routine knows it needs
  // to set a message if enabling cannot be done.
  if (!$status) {
    $GLOBALS['page_manager_enabling_blog'] = TRUE;
  }
}
