<?php
// $Id: node_edit.inc,v 1.4 2009/09/27 03:41:02 merlinofchaos Exp $

/**
 * Specialized implementation of hook_page_manager_task_tasks(). See api-task.html for
 * more information.
 */
function page_manager_node_edit_page_manager_tasks() {
  return array(
    // This is a 'page' task and will fall under the page admin UI
    'task type' => 'page',

    'title' => t('Node add/edit form'),
    'admin title' => t('Node add/edit form'),
    'admin description' => t('When enabled, this overrides the default Drupal behavior for adding or edit nodes at <em>node/%node/edit</em> and <em>node/add/%node_type</em>. If you add variants, you may use selection criteria such as node type or language or user access to provide different edit forms for nodes. If no variant is selected, the default Drupal node edit will be used.'),
    'admin path' => 'node/%node/edit',

    // Menu hooks so that we can alter the node/%node menu entry to point to us.
    'hook menu' => 'page_manager_node_edit_menu',
    'hook menu alter' => 'page_manager_node_edit_menu_alter',

    // This is task uses 'context' handlers and must implement these to give the
    // handler data it needs.
    'handler type' => 'context',
    'get arguments' => 'page_manager_node_edit_get_arguments',
    'get context placeholders' => 'page_manager_node_edit_get_contexts',

    // Allow this to be enabled or disabled:
    'disabled' => variable_get('page_manager_node_edit_disabled', TRUE),
    'enable callback' => 'page_manager_node_edit_enable',
  );
}

/**
 * Callback defined by page_manager_node_edit_page_manager_tasks().
 *
 * Alter the node edit input so that node edit comes to us rather than the
 * normal node edit process.
 */
function page_manager_node_edit_menu_alter(&$items, $task) {
  if (variable_get('page_manager_node_edit_disabled', TRUE)) {
    return;
  }

  $callback = $items['node/%node/edit']['page callback'];
  // Override the node edit handler for our purpose.
  if ($callback == 'node_page_edit' || variable_get('page_manager_override_anyway', FALSE)) {
    $items['node/%node/edit']['page callback'] = 'page_manager_node_edit';
    $items['node/%node/edit']['file path'] = $task['path'];
    $items['node/%node/edit']['file'] = $task['file'];
  }
  else {
    variable_set('page_manager_node_edit_disabled', TRUE);
    if (!empty($GLOBALS['page_manager_enabling_node_edit'])) {
      drupal_set_message(t('Page manager module is unable to enable node/%node/edit because some other module already has overridden with %callback.', array('%callback' => $callback)), 'warning');
    }
    return;
  }

  // Also catch node/add handling:
  foreach (node_type_get_types() as $type) {
    $path = 'node/add/' . str_replace('_', '-', $type->type);
    if ($items[$path]['page callback'] != 'node_add') {
      if (!empty($GLOBALS['page_manager_enabling_node_edit'])) {
        drupal_set_message(t('Page manager module is unable to override @path because some other module already has overridden with %callback. Node edit will be enabled but that edit path will not be overridden.', array('@path' => $path, '%callback' => $items[$path]['page callback'])), 'warning');
      }
      continue;
    }

    $items[$path]['page callback'] = 'page_manager_node_add';
    $items[$path]['file path'] = $task['path'];
    $items[$path]['file'] = $task['file'];
    // Why str_replace things back?
    $items[$path]['page arguments'] = array($type->type);
  }
}

/**
 * Entry point for our overridden node edit.
 *
 * This function asks its assigned handlers who, if anyone, would like
 * to run with it. If no one does, it passes through to Drupal core's
 * node edit, which is node_page_edit().
 */
function page_manager_node_edit($node) {
  // Load my task plugin
  $task = page_manager_get_task('node_edit');

  // Load the node into a context.
  ctools_include('context');
  ctools_include('context-task-handler');
  $contexts = ctools_context_handler_get_task_contexts($task, '', array($node));

  $arg = array(isset($node->nid) ? $node->nid : $node->type);
  $output = ctools_context_handler_render($task, '', $contexts, $arg);
  if ($output === FALSE) {
    // Fall back!
    // We've already built the form with the context, so we can't build it again, or
    // form_clean_id will mess up our ids. But we don't really need to, either:
    $context = current($contexts);
    $output = drupal_render_form($context->form_id, $context->form);
  }

  return $output;
}

/**
 * Callback to handle the process of adding a node.
 *
 * This creates a basic $node and passes that off to page_manager_node_edit().
 * It is modeled after Drupal's node_add() function.
 *
 * Unlike node_add() we do not need to check node_access because that was
 * already checked by the menu system.
 */
function page_manager_node_add($type) {
  global $user;

  $types = node_type_get_types();

  // Initialize settings:
  $node = (object) array(
    'uid' => $user->uid,
    'name' => (isset($user->name) ? $user->name : ''),
    'type' => $type,
    'language' => '',
  );

  drupal_set_title(t('Create @name', array('@name' => $types[$type]->name)));
  return page_manager_node_edit($node);
}

/**
 * Callback to get arguments provided by this task handler.
 *
 * Since this is the node edit and there is no UI on the arguments, we
 * create dummy arguments that contain the needed data.
 */
function page_manager_node_edit_get_arguments($task, $subtask_id) {
  return array(
    array(
      'keyword' => 'node',
      'identifier' => t('Node being edited'),
      'id' => 1,
      'name' => 'node_edit',
      'settings' => array(),
    ),
  );
}

/**
 * Callback to get context placeholders provided by this handler.
 */
function page_manager_node_edit_get_contexts($task, $subtask_id) {
  return ctools_context_get_placeholders_from_argument(page_manager_node_edit_get_arguments($task, $subtask_id));
}

/**
 * Callback to enable/disable the page from the UI.
 */
function page_manager_node_edit_enable($cache, $status) {
  variable_set('page_manager_node_edit_disabled', $status);
  // Set a global flag so that the menu routine knows it needs
  // to set a message if enabling cannot be done.
  if (!$status) {
    $GLOBALS['page_manager_enabling_node_edit'] = TRUE;
  }
}
