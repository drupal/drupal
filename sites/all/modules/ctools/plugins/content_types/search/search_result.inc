<?php
// $Id: search_result.inc,v 1.7 2010/10/11 22:18:24 sdboyer Exp $

if (module_exists('search')) {
  /**
   * Plugins are described by creating a $plugin array which will be used
   * by the system that includes this file.
   */
  $plugin = array(
    'single' => TRUE,
    'title' => t('Search results'),
    'icon' => 'icon_search.png',
    'description' => t('The results of a search using keywords.'),
    'required context' => new ctools_context_required(t('Keywords'), 'string'),
    'category' => t('Widgets'),
    'defaults' => array(
      'type' => 'node',
      'log' => TRUE,
      'override_empty' => FALSE,
      'empty_title' => '',
      'empty' => '',
      'empty_format' => filter_fallback_format(),
      'override_no_key' => FALSE,
      'no_key_title' => '',
      'no_key' => '',
      'no_key_format' => filter_fallback_format(),
    ),
  );
}

/**
 * Render the custom content type.
 */
function ctools_search_result_content_type_render($subtype, $conf, $panel_args, $context) {
  // Display nothing at all if no keywords were entered.
  if (empty($context) || empty($context->data)) {
    if (!empty($conf['override_no_key'])) {
      $block->title = $conf['no_key_title'];
      $block->content = check_markup($conf['no_key'], $conf['no_key_format'], FALSE);
      return $block;
    }
    return;
  }

  $keys = $context->data;

  // Build the content type block.
  $block = new stdClass();
  $block->module  = 'search';
  $block->delta   = 'result';

  $results = '';

  // Need settings for:
  // $no result override

  if (!empty($conf['log'])) {
    // Log the search keys:
    watchdog('search', '%keys (@type).', array('%keys' => $keys, '@type' => module_invoke($conf['type'], 'search', 'name')), WATCHDOG_NOTICE, l(t('results'), $_GET['q']));
  }

  // Collect the search results:
  $results = search_data($keys, $conf['type']);

  if ($results) {
    $block->title = t('Search results');
    $block->content = $results;
  }
  else {
    if (empty($conf['override_empty'])) {
      $block->title = t('Your search yielded no results');
      $block->content = search_help('search#noresults', drupal_help_arg());
    }
    else {
      $block->title = $conf['empty_title'];
      $block->content = check_markup($conf['empty'], $conf['empty_format'], FALSE);
    }
  }

  return $block;
}

/**
 * Returns an edit form for custom type settings.
 */
function ctools_search_result_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];

  // Add js for collapsible fieldsets manually
  drupal_add_js('misc/collapse.js');

  $types = array();
  foreach (module_implements('search') as $name) {
    $types[$name] = module_invoke($name, 'search', 'name', TRUE);
  }

  $form['type'] = array(
    '#type' => 'select',
    '#title' => t('Search type'),
    '#options' => $types,
    '#default_value' => $conf['type'],
  );

  $form['log'] = array(
    '#type' => 'checkbox',
    '#default_value' => $conf['log'],
    '#title' => t('Record a watchdog log entry when searches are made'),
  );

  $form['override_empty'] = array(
    '#type' => 'checkbox',
    '#default_value' => $conf['override_empty'],
    '#title' => t('Override "no result" text'),
  );

  $form['empty_field']['empty_title'] = array(
    '#title' => t('Title'),
    '#type' => 'textfield',
    '#default_value' => $conf['empty_title'],
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('edit-override-empty' => array(1)),
  );

  $form['empty_field']['empty'] = array(
    '#title' => t('No result text'),
    '#type' => 'textarea',
    '#default_value' => $conf['empty'],
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('edit-override-empty' => array(1)),
  );

  $form['empty_field']['format_prefix'] = array(
    '#type' => 'hidden',
    '#id' => 'edit-empty-format',
    '#prefix' => '<div><fieldset id="edit-empty-format" class="collapsed collapsible"><legend>' . t('Input format') . '</legend>',
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('edit-override-empty' => array(1)),
  );
  // Yes, lots of gymnastics to make this fieldset work with dependencies.
  $form['empty_field']['empty_format'] = filter_form($conf['empty_format'], NULL, array('empty_format'));
  unset($form['empty_field']['empty_format']['#type']);

  $form['empty_field']['format_suffix'] = array(
    '#value' => '</fieldset></div>',
  );


  $form['override_no_key'] = array(
    '#type' => 'checkbox',
    '#default_value' => $conf['override_no_key'],
    '#title' => t('Display text if no search keywords were submitted'),
  );

  $form['no_key_field']['no_key_title'] = array(
    '#title' => t('Title'),
    '#type' => 'textfield',
    '#default_value' => $conf['no_key_title'],
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('edit-override-no-key' => array(1)),
  );

  $form['no_key_field']['no_key'] = array(
    '#title' => t('No keywords text'),
    '#type' => 'textarea',
    '#default_value' => $conf['no_key'],
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('edit-override-no-key' => array(1)),
  );

  $form['no_key_field']['format_prefix'] = array(
    '#type' => 'hidden',
    '#id' => 'edit-no-key-format',
    '#prefix' => '<div><fieldset id="edit-no-key-format" class="collapsed collapsible"><legend>' . t('Input format') . '</legend>',
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('edit-override-no-key' => array(1)),
  );
  // Yes, lots of gymnastics to make this fieldset work with dependencies.
  $form['no_key_field']['no_key_format'] = filter_form($conf['no_key_format'], NULL, array('no_key_format'));
  unset($form['no_key_field']['no_key_format']['#type']);

  $form['no_key_field']['format_suffix'] = array(
    '#value' => '</fieldset></div>',
  );
  return $form;
}

/**
 * Submit handler for search form.
 */
function ctools_search_result_content_type_edit_form_submit($form, &$form_state) {
  // Copy everything from our defaults.
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
}

/**
 * Returns the administrative title for a type.
 */
function ctools_search_result_content_type_admin_title($subtype, $conf, $context) {
  $type = module_invoke($conf['type'], 'search', 'name', TRUE);
  return t('@type search result', array('@type' => $type));
}
