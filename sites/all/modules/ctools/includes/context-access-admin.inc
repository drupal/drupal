<?php
// $Id: context-access-admin.inc,v 1.12 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Contains administrative screens for the access control plugins.
 *
 * Access control can be implemented by creating a list of 0 or more access
 * plugins, each with settings. This list can be ANDed together or ORed
 * together. When testing access, each plugin is tested until success
 * or failure can be determined. We use short circuiting techniques to
 * ensure we are as efficient as possible.
 *
 * Access plugins are part of the context system, and as such can require
 * contexts to work. That allows the use of access based upon visibility
 * of an object, or even more esoteric things such as node type, node language
 * etc. Since a lot of access depends on the logged in user, the logged in
 * user should always be provided as a context.
 *
 * In the UI, the user is presented with a table and a 'add access method' select.
 * When added, the user will be presented with the config wizard and, when
 * confirmed, table will be refreshed via AJAX to show the new access method.
 * Each item in the table will have controls to change the settings or remove
 * the item. Changing the settings will invoke the modal for update.
 *
 * Currently the modal is not degradable, but it could be with only a small
 * amount of work.
 *
 * A simple radio
 * control is used to let the user pick the and/or logic.
 *
 * Access control is stored in an array:
 * @code
 *   array(
 *     'plugins' => array(
 *       0 => array(
 *         'name' => 'name of access plugin',
 *         'settings' => array(), // These will be set by the form
 *       ),
 *       // ... as many as needed
 *     ),
 *     'logic' => 'AND', // or 'OR',
 *   ),
 * @endcode
 *
 * To add this widget to your UI, you need to do a little bit of setup.
 *
 * The form will utilize two callbacks, one to get the cached version
 * of the access settings, and one to store the cached version of the
 * access settings. These will be used from AJAX forms, so they will
 * be completely out of the context of this page load and will not have
 * knowledge of anything sent to this form (the 'module' and 'argument'
 * will be preserved through the URL only).
 *
 * The 'module' is used to determine the location of the callback. It
 * does not strictly need to be a module, so that if your module defines
 * multiple systems that use this callback, it can use anything within the
 * module's namespace it likes.
 *
 * When retrieving the cache, the cache may not have already been set up;
 * In order to efficiently use cache space, we want to cache the stored
 * settings *only* when they have changed. Therefore, the get access cache
 * callback should first look for cache, and if it finds nothing, return
 * the original settings.
 *
 * The callbacks:
 * - $module . _ctools_access_get($argument) -- get the 'access' settings
 *   from cache. Must return array($access, $contexts); This callback can
 *   perform access checking to make sure this URL is not being gamed.
 * - $module . _ctools_access_set($argument, $access) -- set the 'access'
 *   settings in cache.
 * - $module . _ctools_access_clear($argument) -- clear the cache.
 *
 * The ctools_object_cache is recommended for this purpose, but you can use
 * any caching mechanism you like. An example:
 *
 * @code{
 *   ctools_include('object-cache');
 *   ctools_object_cache_set("$module:argument", $access);
 * }
 *
 * To utilize this form:
 * @code
 *   ctools_include('context-access-admin');
 *   $form_state = array(
 *     'access' => $access,
 *     'module' => 'module name',
 *     'callback argument' => 'some string',
 *     'contexts' => $contexts, // an array of contexts. Optional if no contexts.
 *     // 'logged-in-user' will be added if not present as the access system
 *     // requires this context.
 *   ),
 *   $output = drupal_build_form('ctools_access_admin_form', $form_state);
 *   if (!empty($form_state['executed'])) {
 *     // save $form_state['access'] however you like.
 *   }
 * @endcode
 *
 * Additionally, you may add 'no buttons' => TRUE if you wish to embed this
 * form into your own, and instead call
 *
 * @code{
 *   $form = array_merge($form, ctools_access_admin_form($form_state));
 * }
 *
 * You'll be responsible for adding a submit button.
 *
 * You may use ctools_access($access, $contexts) which will return
 * TRUE if access is passed or FALSE if access is not passed.
 */

/**
 * Administrative form for access control.
 */
function ctools_access_admin_form($form, &$form_state) {
  ctools_include('context');
  $argument = isset($form_state['callback argument']) ? $form_state['callback argument'] : '';
  $fragment = $form_state['module'];
  if ($argument) {
    $fragment .= '-' . $argument;
  }

  $contexts = isset($form_state['contexts']) ? $form_state['contexts'] : array();

  $form['access_table'] = array(
    '#markup' => ctools_access_admin_render_table($form_state['access'], $fragment, $contexts),
  );

  $form['add-button'] = array(
    '#theme' => 'ctools_access_admin_add',
  );
  // This sets up the URL for the add access modal.
  $form['add-button']['add-url'] = array(
    '#attributes' => array('class' => array("ctools-access-add-url")),
    '#type' => 'hidden',
    '#value' => url("ctools/context/ajax/access/add/$fragment", array('absolute' => TRUE)),
  );

  $plugins = ctools_get_relevant_access_plugins($contexts);
  $options = array();
  foreach ($plugins as $id => $plugin) {
    $options[$id] = $plugin['title'];
  }

  asort($options);

  $form['add-button']['type'] = array(
    // This ensures that the form item is added to the URL.
    '#attributes' => array('class' => array("ctools-access-add-url")),
    '#type' => 'select',
    '#options' => $options,
    '#required' => FALSE,
  );

  $form['add-button']['add'] = array(
    '#type' => 'submit',
    '#attributes' => array('class' => array('ctools-use-modal')),
    '#id' => "ctools-access-add",
    '#value' => t('Add'),
  );

  $form['logic'] = array(
    '#type' => 'radios',
    '#options' => array(
      'and' => t('All criteria must pass.'),
      'or' => t('Only one criteria must pass.'),
    ),
    '#default_value' => isset($form_state['access']['logic']) ? $form_state['access']['logic'] : 'and',
  );

  if (empty($form_state['no buttons'])) {
    $form['buttons']['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#submit' => array('ctools_access_admin_form_submit'),
    );
  }

  return $form;
}

/**
 * Render the table. This is used both to render it initially and to rerender
 * it upon ajax response.
 */
function ctools_access_admin_render_table($access, $fragment, $contexts) {
  ctools_include('ajax');
  ctools_include('modal');
  $rows = array();

  if (empty($access['plugins'])) {
    $access['plugins'] = array();
  }

  foreach ($access['plugins'] as $id => $test) {
    $row    = array();
    $plugin = ctools_get_access_plugin($test['name']);
    $title  = isset($plugin['title']) ? $plugin['title'] : t('Broken/missing access plugin %plugin', array('%plugin' => $test['name']));

    $row[] = array('data' => $title, 'class' => array('ctools-access-title'));

    $description = ctools_access_summary($plugin, $contexts, $test);
    $row[] = array('data' => $description, 'class' => array('ctools-access-description'));

    $operations = ctools_modal_image_button(ctools_image_path('icon-configure.png'), "ctools/context/ajax/access/configure/$fragment/$id", t('Configure settings for this item.'));
    $operations .= ctools_ajax_image_button(ctools_image_path('icon-delete.png'), "ctools/context/ajax/access/delete/$fragment/$id", t('Remove this item.'));

    $row[] = array('data' => $operations, 'class' => array('ctools-access-operations'), 'align' => 'right');

    $rows[] = $row;
  }

  $header = array(
    array('data' => t('Title'), 'class' => array('ctools-access-title')),
    array('data' => t('Description'), 'class' => array('ctools-access-description')),
    array('data' => '', 'class' => array('ctools-access-operations'), 'align' => 'right'),
  );

  if (empty($rows)) {
    $rows[] = array(array('data' => t('No criteria selected, this test will pass.'), 'colspan' => count($header)));
  }

  ctools_modal_add_js();
  return theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('id' => 'ctools-access-table')));
}

/**
 * Theme the 'add' portion of the access form into a table.
 */
function theme_ctools_access_admin_add($vars) {
  $rows = array(array(drupal_render_children($vars['form'])));
  $output = '<div class="container-inline">';
  $output .= theme('table', array('rows' => $rows));
  $output .= '</div>';
  return $output;
}

function ctools_access_admin_form_submit($form, &$form_state) {
  $form_state['access']['logic'] = $form_state['values']['logic'];

  $function = $form_state['module'] . '_ctools_access_clear';
  if (function_exists($function)) {
    $function($form_state['argument']);
  }
}

// --------------------------------------------------------------------------
// AJAX menu entry points.

/**
 * AJAX callback to add a new access test to the list.
 */
function ctools_access_ajax_add($fragment = NULL, $name = NULL) {
  ctools_include('ajax');
  ctools_include('modal');
  ctools_include('context');

  if (empty($fragment) || empty($name)) {
    ctools_ajax_render_error();
  }

  $plugin = ctools_get_access_plugin($name);
  if (empty($plugin)) {
    ctools_ajax_render_error();
  }

  // Separate the fragment into 'module' and 'argument'
  if (strpos($fragment, '-') === FALSE) {
    $module = $fragment;
    $argument = NULL;
  }
  else {
    list($module, $argument) = explode('-', $fragment, 2);
  }

  $function = $module . '_ctools_access_get';
  if (!function_exists($function)) {
    ctools_ajax_render_error(t('Missing callback hooks.'));
  }

  list($access, $contexts) = $function($argument);

  // Make sure we have the logged in user context
  if (!isset($contexts['logged-in-user'])) {
    $contexts['logged-in-user'] = ctools_access_get_loggedin_context();
  }

  if (empty($access['plugins'])) {
    $access['plugins'] = array();
  }

  $test = ctools_access_new_test($plugin);

  $id = $access['plugins'] ? max(array_keys($access['plugins'])) + 1 : 0;
  $access['plugins'][$id] = $test;

  $form_state = array(
    'plugin' => $plugin,
    'id' => $id,
    'test' => &$access['plugins'][$id],
    'access' => &$access,
    'contexts' => $contexts,
    'title' => t('Add criteria'),
    'ajax' => TRUE,
    'modal' => TRUE,
    'modal return' => TRUE,
  );

  $output = ctools_modal_form_wrapper('ctools_access_ajax_edit_item', $form_state);
  if (!isset($output[0])) {
    $function = $module . '_ctools_access_set';
    if (function_exists($function)) {
      $function($argument, $access);
    }

    $table    = ctools_access_admin_render_table($access, $fragment, $contexts);
    $output   = array();
    $output[] = ajax_command_replace('table#ctools-access-table', $table);
    $output[] = ctools_modal_command_dismiss();
  }

  print ajax_render($output);
}

/**
 * AJAX callback to edit an access test in the list.
 */
function ctools_access_ajax_edit($fragment = NULL, $id = NULL) {
  ctools_include('ajax');
  ctools_include('modal');
  ctools_include('context');

  if (empty($fragment) || !isset($id)) {
    ctools_ajax_render_error();
  }

  // Separate the fragment into 'module' and 'argument'
  if (strpos($fragment, '-') === FALSE) {
    $module = $fragment;
    $argument = NULL;
  }
  else {
    list($module, $argument) = explode('-', $fragment, 2);
  }

  $function = $module . '_ctools_access_get';
  if (!function_exists($function)) {
    ctools_ajax_render_error(t('Missing callback hooks.'));
  }

  list($access, $contexts) = $function($argument);

  if (empty($access['plugins'][$id])) {
    ctools_ajax_render_error();
  }

  // Make sure we have the logged in user context
  if (!isset($contexts['logged-in-user'])) {
    $contexts['logged-in-user'] = ctools_access_get_loggedin_context();
  }

  $plugin = ctools_get_access_plugin($access['plugins'][$id]['name']);
  $form_state = array(
    'plugin' => $plugin,
    'id' => $id,
    'test' => &$access['plugins'][$id],
    'access' => &$access,
    'contexts' => $contexts,
    'title' => t('Edit criteria'),
    'ajax' => TRUE,
    'ajax' => TRUE,
    'modal' => TRUE,
    'modal return' => TRUE,
  );

  $output = ctools_modal_form_wrapper('ctools_access_ajax_edit_item', $form_state);
  if (!isset($output[0])) {
    $function = $module . '_ctools_access_set';
    if (function_exists($function)) {
      $function($argument, $access);
    }

    $table    = ctools_access_admin_render_table($access, $fragment, $contexts);
    $output   = array();
    $output[] = ajax_command_replace('table#ctools-access-table', $table);
    $output[] = ctools_modal_command_dismiss();
  }

  print ajax_render($output);
}

/**
 * Form to edit the settings of an access test.
 */
function ctools_access_ajax_edit_item($form, &$form_state) {
  $test = &$form_state['test'];
  $plugin = &$form_state['plugin'];
  if (isset($plugin['required context'])) {
    $form['context'] = ctools_context_selector($form_state['contexts'], $plugin['required context'], $test['context']);
  }
  $form['settings'] = array('#tree' => TRUE);
  if ($function = ctools_plugin_get_function($plugin, 'settings form')) {
    $form = $function($form, $form_state, $test['settings']);
  }

  $form['not'] = array(
    '#type' => 'checkbox',
    '#title' => t('Reverse (NOT)'),
    '#default_value' => !empty($test['not']),
  );

  $form['save'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Validate handler for argument settings.
 */
function ctools_access_ajax_edit_item_validate($form, &$form_state) {
  if ($function = ctools_plugin_get_function($form_state['plugin'], 'settings form validate')) {
    $function($form, $form_state);
  }
}

/**
 * Submit handler for argument settings.
 */
function ctools_access_ajax_edit_item_submit($form, &$form_state) {
  if ($function = ctools_plugin_get_function($form_state['plugin'], 'settings form submit')) {
    $function($form, $form_state);
  }

  $form_state['test']['settings'] = $form_state['values']['settings'];
  if (isset($form_state['values']['context'])) {
    $form_state['test']['context'] = $form_state['values']['context'];
  }
  $form_state['test']['not'] = !empty($form_state['values']['not']);
}

/**
 * AJAX command to remove an access control item.
 */
function ctools_access_ajax_delete($fragment = NULL, $id = NULL) {
  ctools_include('ajax');
  ctools_include('modal');
  ctools_include('context');

  if (empty($fragment) || !isset($id)) {
    ajax_render_error();
  }

  // Separate the fragment into 'module' and 'argument'
  if (strpos($fragment, '-') === FALSE) {
    $module = $fragment;
    $argument = NULL;
  }
  else {
    list($module, $argument) = explode('-', $fragment, 2);
  }

  $function = $module . '_ctools_access_get';
  if (!function_exists($function)) {
    ajax_render_error(t('Missing callback hooks.'));
  }

  list($access, $contexts) = $function($argument);

  if (isset($access['plugins'][$id])) {
    unset($access['plugins'][$id]);
  }

  // re-cache
  $function = $module . '_ctools_access_set';
  if (function_exists($function)) {
    $function($argument, $access);
  }

  $table    = ctools_access_admin_render_table($access, $fragment, $contexts);
  $output   = array();
  $output[] = ajax_command_replace('table#ctools-access-table', $table);

  print ajax_render($output);
}
