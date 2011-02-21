<?php
// $Id: contact_site.inc,v 1.3 2010/01/29 21:21:20 merlinofchaos Exp $

/**
 * Specialized implementation of hook_page_manager_task_tasks(). See api-task.html for
 * more information.
 */
function page_manager_contact_site_page_manager_tasks() {
  if (!module_exists('contact')) {
    return;
  }

  return array(
    // This is a 'page' task and will fall under the page admin UI
    'task type' => 'page',

    'title' => t('Site contact page'),
    'admin title' => t('Site contact page'),
    'admin description' => t('When enabled, this overrides the default Drupal behavior for the site contact page at <em>/contact</em>. If no variant is selected, the default Drupal contact form will be used.'),
    'admin path' => 'contact',

    // Menu hooks so that we can alter the node/%node menu entry to point to us.
    'hook menu alter' => 'page_manager_contact_site_menu_alter',

    // This is task uses 'context' handlers and must implement these to give the
    // handler data it needs.
    'handler type' => 'context',

    // Allow this to be enabled or disabled:
    'disabled' => variable_get('page_manager_contact_site_disabled', TRUE),
    'enable callback' => 'page_manager_contact_site_enable',
  );
}

/**
 * Callback defined by page_manager_contact_site_page_manager_tasks().
 *
 * Alter the node edit input so that node edit comes to us rather than the
 * normal node edit process.
 */
function page_manager_contact_site_menu_alter(&$items, $task) {
  if (variable_get('page_manager_contact_site_disabled', TRUE)) {
    return;
  }

  $callback = $items['contact']['page callback'];
  // Override the node edit handler for our purpose.
  if ($callback == 'contact_site_page' || variable_get('page_manager_override_anyway', FALSE)) {
    $items['contact']['page callback'] = 'page_manager_contact_site';
    $items['contact']['file path'] = $task['path'];
    $items['contact']['file'] = $task['file'];
  }
  else {
    variable_set('page_manager_contact_site_disabled', TRUE);
    if (!empty($GLOBALS['page_manager_enabling_contact_site'])) {
      drupal_set_message(t('Page manager module is unable to enable contact because some other module already has overridden with %callback.', array('%callback' => $callback)), 'warning');
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
function page_manager_contact_site() {
  // Load my task plugin
  $task = page_manager_get_task('contact_site');

  ctools_include('context');
  ctools_include('context-task-handler');
  $output = ctools_context_handler_render($task, '', array(), array());
  if ($output !== FALSE) {
    return $output;
  }

  module_load_include('inc', 'contact', 'contact.pages');
  $function = 'contact_site_page';
  foreach (module_implements('page_manager_override') as $module) {
    $call = $module . '_page_manager_override';
    if (($rc = $call('contact_site')) && function_exists($rc)) {
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
function page_manager_contact_site_enable($cache, $status) {
  variable_set('page_manager_contact_site_disabled', $status);
  // Set a global flag so that the menu routine knows it needs
  // to set a message if enabling cannot be done.
  if (!$status) {
    $GLOBALS['page_manager_enabling_contact_site'] = TRUE;
  }
}
