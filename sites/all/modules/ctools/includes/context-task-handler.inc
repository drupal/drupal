<?php
// $Id: context-task-handler.inc,v 1.35 2011/01/05 20:06:57 merlinofchaos Exp $

/**
 * @file
 * Support for creating 'context' type task handlers.
 *
 * Context task handlers expect the task to provide 0 or more contexts. The
 * task handler should use those contexts as selection rules, as well as
 * rendering with them.
 *
 * The functions and forms in this file should be common to every context type
 * task handler made.
 *
 * Forms:
 * - ...
 */

/**
 * Render a context type task handler given a list of handlers
 * attached to a type.
 *
 * @param $task
 *   The $task object in use.
 * @param $subtask
 *   The id of the subtask in use.
 * @param $contexts
 *   The context objects in use.
 * @param $args
 *   The raw arguments behind the contexts.
 * @param $page
 *   If TRUE then this renderer owns the page and can use theme('page')
 *   for no blocks; if false, output is returned regardless of any no
 *   blocks settings.
 * @return
 *   Either the output or NULL if there was output, FALSE if no handler
 *   accepted the task. If $page is FALSE then the $info block is returned instead.
 */
function ctools_context_handler_render($task, $subtask, $contexts, $args) {
  // Load the landlers, choosing only enabled handlers.
  $handlers = page_manager_load_sorted_handlers($task, $subtask ? $subtask['name'] : '', TRUE);

  $id = ctools_context_handler_get_render_handler($task, $subtask, $handlers, $contexts, $args);
  if ($id) {
    return ctools_context_handler_render_handler($task, $subtask, $handlers[$id], $contexts, $args);
  }
}

/**
 * Figure out which of the listed handlers should be used to render.
 */
function ctools_context_handler_get_render_handler($task, $subtask, $handlers, $contexts, $args) {
  // Try each handler.
  foreach ($handlers as $id => $handler) {
    $plugin = page_manager_get_task_handler($handler->handler);
    // First, see if the handler has a tester.
    $function = ctools_plugin_get_function($plugin, 'test');
    if ($function) {
      $test = $function($handler, $contexts, $args);
      if ($test) {
        return $id;
      }
    }
    else {
      // If not, if it's a 'context' type handler, use the default tester.
      if ($plugin['handler type'] == 'context') {
        $test = ctools_context_handler_default_test($handler, $contexts, $args);
      }
      if ($test) {
        return $id;
      }
    }
  }

  return FALSE;
}

/**
 * Default test function to see if a task handler should be rendered.
 *
 * This tests against the standard selection criteria that most task
 * handlers should be implementing.
 */
function ctools_context_handler_default_test($handler, $base_contexts, $args) {
  ctools_include('context');
  // Add my contexts
  $contexts = ctools_context_handler_get_handler_contexts($base_contexts, $handler);

  // Test.
  return ctools_context_handler_select($handler, $contexts);
}

/**
 * Render a task handler.
 */
function ctools_context_handler_render_handler($task, $subtask, $handler, $contexts, $args, $page = TRUE) {
  $function = page_manager_get_renderer($handler);
  if (!$function) {
    return NULL;
  }

  $info = $function($handler, $contexts, $args);
  if (!$info) {
    return NULL;
  }

  $context = array(
    'args' => $args,
    'contexts' => $contexts,
    'task' => $task,
    'subtask' => $subtask,
    'handler' => $handler
  );
  drupal_alter('ctools_render', $info, $page, $context);

  // If we don't own the page, let the caller deal with rendering.
  if (!$page) {
    return $info;
  }

  if ($subtask) {
    $task_name = page_manager_make_task_name($task['name'], $subtask['name']);
  }
  else {
    $task_name = $task['name'];
  }

  page_manager_get_current_page(array(
    'name' => $task_name,
    'task' => $task,
    'subtask' => $subtask,
    'contexts' => $contexts,
    'arguments' => $args,
    'handler' => $handler,
  ));

  if (!empty($info['response code']) && $info['response code'] != 200) {
    switch ($info['response code']) {
      case 403:
        return MENU_ACCESS_DENIED;
      case 404:
        return MENU_NOT_FOUND;
      case 301:
      case 302:
      case 303:
      case 304:
      case 305:
      case 307:
        $info += array(
          'query' => '',
          'fragment' => '',
        );
        $options = array(
          'query' => $info['query'],
          'fragment' => $info['fragment'],
        );
        return drupal_goto($info['destination'], $options, $info['response code']);
      // @todo -- should other response codes be supported here?
    }
  }

  foreach (ctools_context_handler_get_task_arguments($task, $subtask) as $id => $argument) {
    $plugin = ctools_get_argument($argument['name']);
    $cid = ctools_context_id($argument, 'argument');
    if (!empty($contexts[$cid]) && ($function = ctools_plugin_get_function($plugin, 'breadcrumb'))) {
      $function($argument['settings'], $contexts[$cid]);
    }
  }

  if (isset($info['title'])) {
    drupal_set_title($info['title']);
  }

  // Only directly output if $page was set to true.
  if (!empty($info['no_blocks'])) {
    ctools_set_no_blocks(FALSE);
  }
  return $info['content'];
}

/**
 * Called to execute actions that should happen before a handler is rendered.
 */
function ctools_context_handler_pre_render($handler, $contexts, $args) {
  $plugin = page_manager_get_task_handler($handler->handler);

  if (user_access('administer page manager') && isset($handler->task)) {
    // Provide a tab to edit this context:
    ctools_include('menu');
    $task = page_manager_get_task($handler->task);

    $title = !empty($task['tab title']) ? $task['tab title'] : t('Edit @type', array('@type' => $plugin['title']));
    $trail = array();
    if (!empty($plugin['tab operation'])) {
      if (is_array($plugin['tab operation'])) {
        $trail = $plugin['tab operation'];
      }
      else if (function_exists($plugin['tab operation'])) {
        $trail = $plugin['tab operation']($handler, $contexts, $args);
      }
    }

    ctools_menu_add_tab(array(
      'title' => $title,
      'href' => page_manager_edit_url(page_manager_make_task_name($handler->task, $handler->subtask), $trail),
      'type' => MENU_LOCAL_ACTION
    ));
  }
}

/**
 * Compare arguments to contexts for selection purposes.
 *
 * @param $handler
 *   The handler in question.
 * @param $contexts
 *   The context objects provided by the task.
 *
 * @return
 *   TRUE if these contexts match the selection rules. NULL or FALSE
 *   otherwise.
 */
function ctools_context_handler_select($handler, $contexts) {
  if (empty($handler->conf['access'])) {
    return TRUE;
  }

  ctools_include('context');
  return ctools_access($handler->conf['access'], $contexts);
}

/**
 * Get the array of summary strings for the arguments.
 *
 * These summary strings are used to communicate to the user what
 * arguments the task handlers are selecting.
 *
 * @param $task
 *   The loaded task plugin.
 * @param $subtask
 *   The subtask id.
 * @param $handler
 *   The handler to be checked.
 */
function ctools_context_handler_summary($task, $subtask, $handler) {
  if (empty($handler->conf['access']['plugins'])) {
    return array();
  }

  ctools_include('context');
  $strings = array();
  $contexts = ctools_context_handler_get_all_contexts($task, $subtask, $handler);

  foreach ($handler->conf['access']['plugins'] as $test) {
    $plugin = ctools_get_access_plugin($test['name']);
    if ($string = ctools_access_summary($plugin, $contexts, $test)) {
      $strings[] = $string;
    }
  }

  return $strings;
}

// --------------------------------------------------------------------------
// Tasks and Task handlers can both have their own sources of contexts.
// Sometimes we need all of these contexts at once (when editing
// the task handler, for example) but sometimes we need them separately
// (when a task has contexts loaded and is trying out the task handlers,
// for example). Therefore there are two paths we can take to getting contexts.

/**
 * Load the contexts for a task, using arguments.
 *
 * This creates the base array of contexts, loaded from arguments, suitable
 * for use in rendering.
 */
function ctools_context_handler_get_task_contexts($task, $subtask, $args) {
  $contexts = ctools_context_handler_get_base_contexts($task, $subtask);
  $arguments = ctools_context_handler_get_task_arguments($task, $subtask);
  ctools_context_get_context_from_arguments($arguments, $contexts, $args);

  return $contexts;
}

/**
 * Load the contexts for a task handler.
 *
 * This expands a base set of contexts passed in from a task with the
 * contexts defined on the task handler. The contexts from the task
 * must already have been loaded.
 */
function ctools_context_handler_get_handler_contexts($contexts, $handler) {
  $object = ctools_context_handler_get_handler_object($handler);
  return ctools_context_load_contexts($object, FALSE, $contexts);
}

/**
 * Load the contexts for a task and task handler together.
 *
 * This pulls the arguments from a task and everything else from a task
 * handler and loads them as a group. Since there is no data, this loads
 * the contexts as placeholders.
 */
function ctools_context_handler_get_all_contexts($task, $subtask, $handler) {
  $contexts = array();

  $object = ctools_context_handler_get_task_object($task, $subtask, $handler);
  $contexts = ctools_context_load_contexts($object, TRUE, $contexts);
  ctools_context_handler_set_access_restrictions($task, $subtask, $handler, $contexts);
  return $contexts;
}

/**
 * Create an object suitable for use with the context system that kind of
 * expects things in a certain, kind of clunky format.
 */
function ctools_context_handler_get_handler_object($handler) {
  $object = new stdClass;
  $object->name = $handler->name;
  $object->contexts = isset($handler->conf['contexts']) ? $handler->conf['contexts'] : array();
  $object->relationships = isset($handler->conf['relationships']) ? $handler->conf['relationships'] : array();

  return $object;
}

/**
 * Create an object suitable for use with the context system that kind of
 * expects things in a certain, kind of clunky format. This one adds in
 * arguments from the task.
 */
function ctools_context_handler_get_task_object($task, $subtask, $handler) {
  $object = new stdClass;
  $object->name = !empty($handler->name) ? $handler->name : 'temp';
  $object->base_contexts = ctools_context_handler_get_base_contexts($task, $subtask, TRUE);
  $object->arguments = ctools_context_handler_get_task_arguments($task, $subtask);
  $object->contexts = isset($handler->conf['contexts']) ? $handler->conf['contexts'] : array();
  $object->relationships = isset($handler->conf['relationships']) ? $handler->conf['relationships'] : array();

  return $object;
}

/**
 * Get base contexts from a task, if it has any.
 *
 * Tasks can get their contexts either from base contexts or arguments; base
 * contexts extract their information from the environment.
 */
function ctools_context_handler_get_base_contexts($task, $subtask, $placeholders = FALSE) {
  if ($function = ctools_plugin_get_function($task, 'get base contexts')) {
    return $function($task, $subtask, $placeholders);
  }

  return array();
}

/**
 * Get the arguments from a task that are used to load contexts.
 */
function ctools_context_handler_get_task_arguments($task, $subtask) {
  if ($function = ctools_plugin_get_function($task, 'get arguments')) {
    return $function($task, $subtask);
  }

  return array();
}

/**
 * Set any access restrictions on the contexts for a handler.
 *
 * Both the task and the handler could add restrictions to the contexts
 * based upon the access control. These restrictions might be useful
 * to limit what kind of content appears in the add content dialog;
 * for example, if we have an access item that limits a node context
 * to only 'story' and 'page' types, there is no need for content that
 * only applies to the 'poll' type to appear.
 */
function ctools_context_handler_set_access_restrictions($task, $subtask, $handler, &$contexts) {
  // First, for the task:
  if ($function = ctools_plugin_get_function($task, 'access restrictions')) {
    $function($task, $subtask, $contexts);
  }

  // Then for the handler:
  if (isset($handler->conf['access'])) {
    ctools_access_add_restrictions($handler->conf['access'], $contexts);
  }
}

/**
 * Form to choose context based selection rules for a task handler.
 *
 * The configuration will be assumed to go simply in $handler->conf and
 * will be keyed by the argument ID.
 */
function ctools_context_handler_edit_criteria($form, &$form_state) {
  if (!isset($form_state['handler']->conf['access'])) {
    $form_state['handler']->conf['access'] = array();
  }

  ctools_include('context');
  ctools_include('modal');
  ctools_include('ajax');
  ctools_modal_add_plugin_js(ctools_get_access_plugins());
  ctools_include('context-access-admin');
  $form_state['module'] = 'page_manager_task_handler';
  // Encode a bunch of info into the argument so we can get our cache later
  $form_state['callback argument'] = $form_state['task_name'] . '*' . $form_state['handler']->name;
  $form_state['access'] = $form_state['handler']->conf['access'];
  $form_state['no buttons'] = TRUE;
  $form_state['contexts'] = ctools_context_handler_get_all_contexts($form_state['task'], $form_state['subtask'], $form_state['handler']);

  $form['markup'] = array(
    '#markup' => '<div class="description">' .
    t('If there is more than one variant on a page, when the page is visited each variant is given an opportunity to be displayed. Starting from the first variant and working to the last, each one tests to see if its selection rules will pass. The first variant that meets its criteria (as specified below) will be used.') .
    '</div>',
  );
  $form = ctools_access_admin_form($form, $form_state);
  return $form;
}

/**
 * Submit handler for rules selection
 */
function ctools_context_handler_edit_criteria_submit(&$form, &$form_state) {
  $form_state['handler']->conf['access']['logic'] = $form_state['values']['logic'];
}

/**
 * Edit contexts that go with this panel.
 */
function ctools_context_handler_edit_context($form, &$form_state) {
  ctools_include('context-admin');
  ctools_context_admin_includes();

  $handler = $form_state['handler'];
  $page = $form_state['page'];
  $cache_name = $handler->name ? $handler->name : 'temp';
  if (isset($page->context_cache[$cache_name])) {
    $cache = $page->context_cache[$cache_name];
  }
  else {
    $cache = ctools_context_handler_get_task_object($form_state['task'], $form_state['subtask'], $form_state['handler']);
    $form_state['page']->context_cache[$cache_name] = $cache;
  }

  $form['right'] = array(
    '#prefix' => '<div class="clearfix"><div class="right-container">',
    '#suffix' => '</div>',
  );

  $form['left'] = array(
    '#prefix' => '<div class="left-container">',
    '#suffix' => '</div></div>',
  );

  $module = 'page_manager_context::' . $page->task_name;
  ctools_context_add_context_form($module, $form, $form_state, $form['right']['contexts_table'], $cache);
  ctools_context_add_relationship_form($module, $form, $form_state, $form['right']['relationships_table'], $cache);

  $theme_vars = array();
  $theme_vars['object'] = $cache;
  $theme_vars['header'] = t('Summary of contexts');
  $form['left']['summary'] = array(
    '#prefix' => '<div class="page-manager-contexts">',
    '#suffix' => '</div>',
    '#markup' => theme('ctools_context_list', $theme_vars),
  );

  $form_state['context_object'] = &$cache;
  return $form;
}

/**
 * Process submission of the context edit form.
 */
function ctools_context_handler_edit_context_submit(&$form, &$form_state) {
  $handler = &$form_state['handler'];

  $cache_name = $handler->name ? $handler->name : 'temp';

  $handler->conf['contexts'] = $form_state['context_object']->contexts;
  $handler->conf['relationships'] = $form_state['context_object']->relationships;
  if (isset($form_state['page']->context_cache[$cache_name])) {
    unset($form_state['page']->context_cache[$cache_name]);
  }
}

