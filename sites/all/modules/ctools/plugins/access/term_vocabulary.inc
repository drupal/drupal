<?php
// $Id: term_vocabulary.inc,v 1.9 2011/01/05 20:34:46 merlinofchaos Exp $

/**
 * @file
 * Plugin to provide access control based upon term vocabulary
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Taxonomy: vocabulary"),
  'description' => t('Control access by vocabulary.'),
  'callback' => 'ctools_term_vocabulary_ctools_access_check',
  'default' => array('vids' => array()),
  'settings form' => 'ctools_term_vocabulary_ctools_access_settings',
  'settings form submit' => 'ctools_term_vocabulary_ctools_access_settings_submit',
  'summary' => 'ctools_term_vocabulary_ctools_access_summary',
  'required context' => new ctools_context_required(t('Vocabulary'), array('taxonomy_term', 'terms', 'taxonomy_vocabulary')),
);

/**
 * Settings form for the 'by term_vocabulary' access plugin
 */
function ctools_term_vocabulary_ctools_access_settings($form, &$form_state, $conf) {
  $options = array();
  $vocabularies = taxonomy_get_vocabularies();
  foreach ($vocabularies as $voc) {
    $options[$voc->vid] = check_plain($voc->name);
  }

  $form['settings']['vids'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Vocabularies'),
    '#options' => $options,
    '#description' => t('Only the checked vocabularies will be valid.'),
    '#default_value' => $conf['vids'],
  );
  return $form;
}

/**
 * Compress the term_vocabularys allowed to the minimum.
 */
function ctools_term_vocabulary_ctools_access_settings_submit($form, &$form_state) {
  $form_state['values']['settings']['vids'] = array_filter($form_state['values']['settings']['vids']);
}

/**
 * Check for access.
 */
function ctools_term_vocabulary_ctools_access_check($conf, $context) {
  // As far as I know there should always be a context at this point, but this
  // is safe.
  if (empty($context) || empty($context->data) || empty($context->data->vid)) {
    return FALSE;
  }

  if (array_filter($conf['vids']) && empty($conf['vids'][$context->data->vid])) {
    return FALSE;
  }

  return TRUE;
}

/**
 * Provide a summary description based upon the checked term_vocabularys.
 */
function ctools_term_vocabulary_ctools_access_summary($conf, $context) {
  if (!isset($conf['type'])) {
    $conf['type'] = array();
  }
  $vocabularies = taxonomy_get_vocabularies();

  $names = array();
  foreach (array_filter($conf['vids']) as $vid) {
    $names[] = check_plain($vocabularies[$vid]->name);
  }

  if (empty($names)) {
    return t('@identifier is any vocabulary', array('@identifier' => $context->identifier));
  }

  return format_plural(count($names), '@identifier vocabulary is "@vids"', '@identifier vocabulary is one of "@vids"', array('@vids' => implode(', ', $names), '@identifier' => $context->identifier));
}

