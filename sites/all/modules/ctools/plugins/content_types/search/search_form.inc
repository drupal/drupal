<?php
// $Id: search_form.inc,v 1.5 2010/12/31 21:24:40 merlinofchaos Exp $

if (module_exists('search')) {
  /**
   * Plugins are described by creating a $plugin array which will be used
   * by the system that includes this file.
   */
  $plugin = array(
    'single' => TRUE,
    'title' => t('Advanced search form'),
    'icon' => 'icon_search.png',
    'description' => t('A search form with advanced options.'),
    'required context' => new ctools_context_optional(t('Keywords'), 'string'),
    'category' => t('Widgets'),
    'defaults' => array(
      'type' => 'node',
      'form' => 'advanced',
      'path_type' => 'default',
      'path' => '',
      'override_prompt' => FALSE,
      'prompt' => '',
    ),
  );
}

/**
 * Render the custom content type.
 */
function ctools_search_form_content_type_render($subtype, $conf, $panel_args, $context) {
  if (empty($context) || empty($context->data)) {
    $keys = '';
  }
  else {
    $keys = $context->data;
  }

  // Build the content type block.
  $block = new stdClass();
  $block->module  = 'search';
  $block->delta   = 'form';
  $block->title   = '';

  switch ($conf['path_type']) {
    default:
    case 'default':
      $path = 'search/' . $conf['type'];
      break;
    case 'same':
      $path = $_GET['q'];
      $path = str_replace($keys, '', $path);
      break;
    case 'custom':
      $path = $conf['path'];
      break;
  }

  $prompt = $conf['override_prompt'] ? $conf['prompt'] : NULL;

  $form_state = array(
    'no_redirect' => TRUE,
    'build_info' => array(
      'args' => array($path, $keys, $conf['type'], $prompt),
    ),
  );

  module_load_include('inc', 'search', 'search.pages');

  $form_id = $conf['form'] == 'simple' ? 'ctools_search_form' : 'search_form';
  $block->content = drupal_build_form($form_id, $form_state);

  // We do the redirect manually because the built in search form is stupid
  // and won't redirect even though action is a valid argument for it.
  if (empty($block->content)) {
    drupal_goto($path . '/' . $form_state['values']['processed_keys']);
  }

  return $block;
}

/**
 * Returns an edit form for custom type settings.
 */
function ctools_search_form_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];

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

  $form['form'] = array(
    '#type' => 'select',
    '#title' => t('Search form'),
    '#options' => array(
      'simple' => t('Simple'),
      'advanced' => t('Advanced'),
    ),
    '#default_value' => $conf['form'],
    '#description' => t('The advanced form may have additional options based upon the search type. For example the advanced content (node) search form will allow searching by node type and taxonomy term.'),
  );

  $form['path_type'] = array(
    '#prefix' => '<div class="container-inline">',
    '#type' => 'select',
    '#title' => t('Path'),
    '#options' => array(
      'default' => t('Default'),
      'same' => t('Same page'),
      'custom' => t('Custom'),
    ),
    '#default_value' => $conf['path_type'],
  );

  $form['path'] = array(
    '#type' => 'textfield',
    '#default_value' => $conf['path'],
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('edit-path-type' => array('custom')),
    '#suffix' => '</div>',
  );

  $form['override_prompt'] = array(
    '#prefix' => '<div class="container-inline">',
    '#type' => 'checkbox',
    '#default_value' => $conf['override_prompt'],
    '#title' => t('Override default prompt'),
  );

  $form['prompt'] = array(
    '#type' => 'textfield',
    '#default_value' => $conf['prompt'],
    '#process' => array('ctools_dependent_process'),
    '#dependency' => array('edit-override-prompt' => array(1)),
    '#suffix' => '</div>',
  );
  return $form;
}

/**
 * Submit handler for search form.
 */
function ctools_search_form_content_type_edit_form_submit($form, &$form_state) {
  // Copy everything from our defaults.
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
}

/**
 * Returns the administrative title for a type.
 */
function ctools_search_form_content_type_admin_title($subtype, $conf, $context) {
  $type = module_invoke($conf['type'], 'search', 'name', TRUE);
  return t('@type search form', array('@type' => $type));
}

/**
 * Form alter the submit/validate onto our customized search form.
 */
function ctools_form_ctools_search_form_alter(&$form, &$form_state) {
  $form['#validate'] = array('search_form_validate');
  $form['#submit'] = array('search_form_submit');
}
