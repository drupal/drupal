<?php
// $Id: term.inc,v 1.13 2011/01/01 01:14:24 merlinofchaos Exp $

/**
 * @file
 *
 * Plugin to provide a term context
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Taxonomy term"),
  'description' => t('A single taxonomy term object.'),
  'context' => 'ctools_context_create_term',
  'edit form' => 'ctools_context_term_settings_form',
  'defaults' => array(
    'vid' => '',
    'tid' => '',
  ),
  'keyword' => 'term',
  'context name' => 'term',
  'convert list' => array(
    'tid' => t('Term ID'),
    'name' => t('Term name'),
    'name_dashed' => t('Term name, lowercased and spaces converted to dashes'),
    'description' => t('Term Description'),
    'vid' => t('Vocabulary ID'),
  ),
  'convert' => 'ctools_context_term_convert',
);

/**
 * It's important to remember that $conf is optional here, because contexts
 * are not always created from the UI.
 */
function ctools_context_create_term($empty, $data = NULL, $conf = FALSE) {
  $context = new ctools_context('term');
  $context->plugin = 'term';

  if ($empty) {
    return $context;
  }

  if ($conf && isset($data['tid'])) {
    $data = taxonomy_term_load($data['tid']);
  }

  if (!empty($data)) {
    $context->data        = $data;
    $context->title       = $data->name;
    $context->argument    = $data->tid;
    $context->description = $data->description;
    return $context;
  }
}

function ctools_context_term_settings_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $form['vid'] = array(
    '#title' => t('Vocabulary'),
    '#type' => 'select',
    '#options' => array(),
    '#description' => t('Select the vocabulary for this form.'),
    '#id' => 'ctools-select-vid',
    '#default_value' => $conf['vid'],
  );

  $description = '';
  if (!empty($conf['tid'])) {
    $info = db_query('SELECT * FROM {taxonomy_term_data} WHERE tid = :tid', array(':tid' => $conf['tid']))->fetchObject();
    if ($info) {
      $description = ' ' . t('Currently set to @term. Enter another term if you wish to change the term.', array('@term' => $info->name));
    }
  }

  ctools_include('dependent');
  $options = array();

  $form['taxonomy']['#tree'] = TRUE;

  foreach (taxonomy_get_vocabularies() as $vid => $vocabulary) {
    $options[$vid] = $vocabulary->name;
    $form['taxonomy'][$vocabulary->vid] = array(
      '#type' => 'textfield',
      '#description' => t('Select a term from @vocabulary.', array('@vocabulary' => $vocabulary->name)) . $description,
      '#autocomplete_path' => 'taxonomy/autocomplete/' . $vocabulary->vid,
      '#process' => array('ctools_dependent_process'),
      '#dependency' => array('ctools-select-vid' => array($vocabulary->vid)),
    );

  }

  $form['vid']['#options'] = $options;

  $form['tid'] = array(
    '#type' => 'value',
    '#value' => $conf['tid'],
  );

  $form['set_identifier'] = array(
    '#type' => 'checkbox',
    '#default_value' => FALSE,
    '#title' => t('Reset identifier to term title'),
    '#description' => t('If checked, the identifier will be reset to the term name of the selected term.'),
  );

  return $form;
}

/**
 * Validate a term.
 */
function ctools_context_term_settings_form_validate($form, &$form_state) {
  // Validate the autocomplete
  $vid = $form_state['values']['vid'];
  if (empty($form_state['values']['tid']) && empty($form_state['values']['taxonomy'][$vid])) {
    form_error($form['taxonomy'][$vid], t('You must select a term.'));
    return;
  }

  if (empty($form_state['values']['taxonomy'][$vid])) {
    return;
  }

  $term = db_query('SELECT tid FROM {taxonomy_term_data} WHERE LOWER(name) = LOWER(:name) AND vid = :vid', array(':name' => $form_state['values']['taxonomy'][$vid], ':vid' => $vid))->fetchObject();

  if (!$term) {
    form_error($form['taxonomy'][$vid], t('Invalid term selected.'));
  }
  else {
    form_set_value($form['tid'], $term->tid, $form_state);
  }
}

function ctools_context_term_settings_form_submit($form, &$form_state) {
  if ($form_state['values']['set_identifier']) {
    $term = db_query('SELECT tid, name FROM {taxonomy_term_data} WHERE LOWER(tid) = :tid', array(':tid' => $form_state['values']['tid']))->fetchObject();
    $form_state['values']['identifier'] = $term->name;
  }

  $form_state['conf']['tid'] = $form_state['values']['tid'];
  $form_state['conf']['vid'] = $form_state['values']['vid'];
}

/**
 * Convert a context into a string.
 */
function ctools_context_term_convert($context, $type) {
  switch ($type) {
    case 'tid':
      return $context->data->tid;
    case 'name':
      return $context->data->name;
    case 'name_dashed':
      return drupal_strtolower(str_replace(' ', '-', $context->data->name));
    case 'vid':
      return $context->data->vid;
    case 'description':
      return $context->data->description;
  }
}
