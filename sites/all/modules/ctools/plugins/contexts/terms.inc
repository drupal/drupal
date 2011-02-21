<?php
// $Id: terms.inc,v 1.10 2010/10/15 21:06:58 merlinofchaos Exp $

/**
 * @file
 *
 * Plugin to provide a terms context
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Taxonomy terms"),
  'description' => t('Multiple taxonomy terms, as a group.'),
  'context' => 'ctools_context_create_terms',
  'keyword' => 'terms',
  'no ui' => TRUE,
  'context name' => 'terms',
  'convert list' => array(
    'tid' => t('Term ID of first term'),
    'tids' => t('Term ID of all term, separated by + or ,'),
    'name' => t('Term name of first term'),
    'name_dashed' => t('Term name of first term, lowercased and spaces converted to dashes'),
    'names' => t('Term name of all terms, separated by + or ,'),
    'names_dashed' => t('Term name of all terms, separated by + or , and lowercased and spaces converted to dashes'),
    'vid' => t('Vocabulary ID of first term'),
  ),
  'convert' => 'ctools_context_terms_convert',
);

/**
 * It's important to remember that $conf is optional here, because contexts
 * are not always created from the UI.
 */
function ctools_context_create_terms($empty, $data = NULL, $conf = FALSE) {
  // The input is expected to be an object as created by ctools_break_phrase
  // which contains a group of terms.

  $context = new ctools_context(array('terms', 'term'));
  $context->plugin = 'terms';

  if ($empty) {
    return $context;
  }

  if (!empty($data) && is_object($data)) {
    $context->operator = $data->operator;
    $context->tids     = $data->value;
    if (!isset($data->term)) {
      // load the first term:
      reset($context->tids);
      $data->term = taxonomy_term_load(current($context->tids));
    }
    $context->data     = $data->term;
    $context->title    = $data->term->name;
    $context->argument = implode($context->operator == 'or' ? '+' : ',', array_unique($context->tids));
    return $context;
  }
}

/**
 * Convert a context into a string.
 */
function ctools_context_terms_convert($context, $type) {
  switch ($type) {
    case 'tid':
      return $context->data->tid;
    case 'tids':
      return $context->argument;
    case 'name':
      return $context->data->name;
    case 'name_dashed':
      return drupal_strtolower(str_replace(' ', '-', $context->data->name));
    case 'names':
    case 'names_dashed':
      // We only run this query if this item was requested:
      if (!isset($context->names)) {
        if (empty($context->tids)) {
          $context->names = '';
        }
        else {
          $result = db_query('SELECT tid, name FROM {taxonomy_term_data} WHERE tid IN :tids', array(':tids' => $context->tids));
          foreach ($result as $term) {
            $names[$term->tid] = $term->name;
            if ($type == 'names_dashed') {
              $names[$term->tid] = drupal_strtolower(str_replace(' ', '-', $names[$term->tid]));
            }
          }
          $context->names = implode($context->operator == 'or' ? ' + ' : ', ', $names);
        }
      }
      return $context->names;
    case 'vid':
      return $context->data->vid;
  }
}
