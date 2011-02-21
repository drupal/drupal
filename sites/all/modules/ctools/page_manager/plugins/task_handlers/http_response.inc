<?php
// $Id: http_response.inc,v 1.2 2010/10/11 22:18:24 sdboyer Exp $

/**
 * @file
 *
 * This is the task handler plugin to handle generating 403, 404 and 301 response codes.
 */

// Plugin definition
$plugin = array(
  // is a 'context' handler type, meaning it supports the API of the
  // context handlers provided by ctools context plugins.
  'handler type' => 'context',
  'visible' => TRUE, // may be added up front.

  // Administrative fields.
  'title' => t('HTTP response code'),
  'admin summary' =>'page_manager_http_response_admin_summary',
  'admin title' => 'page_manager_http_response_title',
  'operations' => array(
    'settings' => array(
      'title' => t('General'),
      'description' => t('Change general settings for this variant.'),
      'form' => 'page_manager_http_response_edit_settings',
    ),
    'criteria' => array(
      'title' => t('Selection rules'),
      'description' => t('Control the criteria used to decide whether or not this variant is used.'),
      'ajax' => FALSE,
      'form' => array(
        'order' => array(
          'form' => t('Selection rules'),
        ),
        'forms' => array(
          'form' => array(
            'include' => drupal_get_path('module', 'ctools') . '/includes/context-task-handler.inc',
            'form id' => 'ctools_context_handler_edit_criteria',
          ),
        ),
      ),
    ),
    'context' => array(
      'title' => t('Contexts'),
      'ajax' => FALSE,
      'description' => t('Add additional context objects to this variant that can be used by the content.'),
      'form' => array(
        'order' => array(
          'form' => t('Context'),
        ),
        'forms' => array(
          'form' => array(
            'include' => drupal_get_path('module', 'ctools') . '/includes/context-task-handler.inc',
            'form id' => 'ctools_context_handler_edit_context',
          ),
        ),
      ),
    ),
  ),

  // Callback to render the data.
  'render' => 'page_manager_http_response_render',

  'add features' => array(
    'criteria' => t('Selection rules'),
    'context' => t('Contexts'),
  ),
  // Where to go when finished.
  'add finish' => 'settings',

  'required forms' => array(
    'settings' => t('Panel settings'),
  ),

  'edit forms' => array(
    'criteria' => t('Selection rules'),
    'settings' => t('General'),
    'context' => t('Contexts'),
  ),
  'forms' => array(
    'settings' => array(
      'form id' => 'page_manager_http_response_edit_settings',
    ),
    'context' => array(
      'include' => drupal_get_path('module', 'ctools') . '/includes/context-task-handler.inc',
      'form id' => 'ctools_context_handler_edit_context',
    ),
    'criteria' => array(
      'include' => drupal_get_path('module', 'ctools') . '/includes/context-task-handler.inc',
      'form id' => 'ctools_context_handler_edit_criteria',
    ),
  ),
  'default conf' => array(
    'title' => t('HTTP response code'),
    'contexts' => array(),
    'relationships' => array(),
    'code' => '404',
    'destination' => '',
  ),
);

/**
 * Provide a list of the response codes we support.
 *
 * Abstracted so it can be more readily used both on input and output.
 */
function page_manager_http_response_codes() {
  return array(
    404 => t('404 Page not found'),
    403 => t('403 Access denied'),
    301 => t('301 Redirect'),
  );
}

function page_manager_http_response_admin_summary($handler, $task, $subtask, $page, $show_title = TRUE) {
  $task_name = page_manager_make_task_name($task['name'], $subtask['name']);
  $output = '';

  ctools_include('context');
  ctools_include('context-task-handler');

  // Get the operations
  $operations = page_manager_get_operations($page);

  // Get operations for just this handler.
  $operations = $operations['handlers']['children'][$handler->name]['children']['actions']['children'];
  $args = array('handlers', $handler->name, 'actions');
  $rendered_operations = page_manager_render_operations($page, $operations, array(), array('class' => array('actions')), 'actions', $args);

  $plugin = page_manager_get_task_handler($handler->handler);

  $object = ctools_context_handler_get_task_object($task, $subtask, $handler);
  $display->context = ctools_context_load_contexts($object, TRUE);

  $access = ctools_access_group_summary(!empty($handler->conf['access']) ? $handler->conf['access'] : array(), $display->context);
  if ($access) {
    $access = t('This panel will be selected if @conditions.', array('@conditions' => $access));
  }
  else {
    $access = t('This panel will always be selected.');
  }

  $rows = array();

  $type = $handler->type == t('Default') ? t('In code') : $handler->type;
  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Storage')),
    array('class' => array('page-summary-data'), 'data' => $type),
    array('class' => array('page-summary-operation'), 'data' => ''),
  );

  if (!empty($handler->disabled)) {
    $link = l(t('Enable'), page_manager_edit_url($task_name, array('handlers', $handler->name, 'actions', 'enable')));
    $text = t('Disabled');
  }
  else {
    $link = l(t('Disable'), page_manager_edit_url($task_name, array('handlers', $handler->name, 'actions', 'disable')));
    $text = t('Enabled');
  }

  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Status')),
    array('class' => array('page-summary-data'), 'data' => $text),
    array('class' => array('page-summary-operation'), 'data' => $link),
  );

  $link = l(t('Edit'), page_manager_edit_url($task_name, array('handlers', $handler->name, 'criteria')));
  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Selection rule')),
    array('class' => array('page-summary-data'), 'data' => $access),
    array('class' => array('page-summary-operation'), 'data' => $link),
  );

  $link = l(t('Edit'), page_manager_edit_url($task_name, array('handlers', $handler->name, 'settings')));
  $codes = page_manager_http_response_codes();
  $rows[] = array(
    array('class' => array('page-summary-label'), 'data' => t('Response code')),
    array('class' => array('page-summary-data'), 'data' => $codes[$handler->conf['code']]),
    array('class' => array('page-summary-operation'), 'data' => $link),
  );

  $info = theme('table', array('header' => array(), 'rows' => $rows, 'attributes' => array('class' => array('page-manager-handler-summary'))));

  $title = $handler->conf['title'];
  if ($title != t('Panel')) {
    $title = t('Panel: @title', array('@title' => $title));
  }

  $output .= '<div class="clearfix">';
  if ($show_title) {
  $output .= '<div class="handler-title clearfix">';
    $output .= '<div class="actions handler-actions">' . $rendered_operations['actions'] . '</div>';
    $output .= '<span class="title-label">' . $title . '</span>';
  }

  $output .= '</div>';
  $output .= $info;
  $output .= '</div>';

  return $output;
}

/**
 * Set up a title for the panel based upon the selection rules.
 */
function page_manager_http_response_title($handler, $task, $subtask) {
  if (isset($handler->conf['title'])) {
    return check_plain($handler->conf['title']);
  }
  else {
    return t('HTTP response code');
  }
}

/**
 * General settings for the panel
 */
function page_manager_http_response_edit_settings(&$form, &$form_state) {
  $conf = $form_state['handler']->conf;
  $form['title'] = array(
    '#type' => 'textfield',
    '#default_value' => $conf['title'],
    '#title' => t('Administrative title'),
    '#description' => t('Administrative title of this variant.'),
  );

  $form['code'] = array(
    '#title' => t('Response code'),
    '#type' => 'select',
    '#options' => page_manager_http_response_codes(),
    '#default_value' => $conf['code'],
  );

  ctools_include('dependent');
  $form['destination'] = array(
    '#type' => 'textfield',
    '#title' => t('Redirect destination'),
    '#default_value' => $conf['destination'],
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('edit-code' => array(301)),
    '#description' => t('Enter the path to redirect to. You may use keyword substitutions from contexts. You can use external urls (http://www.example.com/foo) or internal urls (node/1).'),
  );

  return $form;
}

function page_manager_http_response_edit_settings_submit(&$form, &$form_state) {
  $form_state['handler']->conf['title'] = $form_state['values']['title'];
  $form_state['handler']->conf['code'] = $form_state['values']['code'];
  $form_state['handler']->conf['destination'] = $form_state['values']['destination'];
}

function page_manager_http_response_render($handler, $base_contexts, $args, $test = TRUE) {
  // Go through arguments and see if they match.
  ctools_include('context');
  ctools_include('context-task-handler');

  // Add my contexts
  $contexts = ctools_context_handler_get_handler_contexts($base_contexts, $handler);

  // Test.
  if ($test && !ctools_context_handler_select($handler, $contexts)) {
    return;
  }

  if (isset($handler->handler)) {
    ctools_context_handler_pre_render($handler, $contexts, $args);
  }

  $info['response code'] = $handler->conf['code'];
  if ($info['response code'] == 301) {
    $path = ctools_context_keyword_substitute($handler->conf['destination'], array(), $contexts);
    $url = parse_url($path);
    if (isset($url['query'])) {
      $path = strtr($path, array('?' . $url['query'] => ''));
      $info['query'] = $url['query'];
    }
    if (isset($url['fragment'])) {
      $path = strtr($path, array('#' . $url['fragment'] => ''));
      $info['fragment'] = $url['fragment'];
    }

    $info['destination'] = $path;
  }

  return $info;
}