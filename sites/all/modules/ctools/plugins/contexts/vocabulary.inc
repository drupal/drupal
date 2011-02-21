<?php
// $Id: vocabulary.inc,v 1.7 2010/10/11 22:18:23 sdboyer Exp $

/**
 * @file
 *
 * Plugin to provide a vocabulary context
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Taxonomy vocabulary"),
  'description' => t('A single taxonomy vocabulary object.'),
  'context' => 'ctools_context_create_vocabulary',
  'edit form' => 'ctools_context_vocabulary_settings_form',
  'defaults' => array('vid' => ''),
  'keyword' => 'vocabulary',
  'context name' => 'vocabulary',
);

/**
 * It's important to remember that $conf is optional here, because contexts
 * are not always created from the UI.
 */
function ctools_context_create_vocabulary($empty, $data = NULL, $conf = FALSE) {
  $context = new ctools_context('vocabulary');
  $context->plugin = 'vocabulary';

  if ($empty) {
    return $context;
  }

  if ($conf && isset($data['vid'])) {
    $data = taxonomy_vocabulary_load($data['vid']);
  }

  if (!empty($data)) {
    $context->data     = $data;
    $context->title    = $data->name;
    $context->argument = $data->vid;
    return $context;
  }
}

function ctools_context_vocabulary_settings_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $options = array();
  foreach (taxonomy_get_vocabularies() as $vid => $vocabulary) {
    $options[$vid] = $vocabulary->name;
  }

  $form['vid'] = array(
    '#title' => t('Vocabulary'),
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => $conf['vid'],
    '#description' => t('Select the vocabulary for this form.'),
  );

  return $form;
}

function ctools_context_vocabulary_settings_form_submit($form, &$form_state) {
  $form_state['conf']['vid'] = $form_state['values']['vid'];
}
