<?php
// $Id: terms_from_node.inc,v 1.2 2010/10/11 22:18:23 sdboyer Exp $

/**
 * @file
 * Plugin to provide an relationship handler for all terms from node.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Multiple terms from node'),
  'keyword' => 'terms',
  'description' => t('Adds a taxonomy terms from a node context; if multiple terms are selected, they wil be concatenated.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'context' => 'ctools_terms_from_node_context',
  'edit form' => 'ctools_terms_from_node_settings_form',
  'defaults' => array('vid' => array(), 'concatenator' => ','),
);

/**
 * Return a new context based on an existing context.
 */
function ctools_terms_from_node_context($context, $conf) {
  // If unset it wants a generic, unfilled context, which is just NULL.
  if (empty($context->data)) {
    return ctools_context_create_empty('terms', NULL);
  }

  // Collect all terms for the chosen vocabulary and concatenate them.
  if (isset($context->data->taxonomy)) {
    $terms = array();
    foreach ($context->data->taxonomy as $term) {
      if (in_array($term->vid, $conf['vid'])) {
        $terms[] = $term->tid;
      }
    }

    if (!empty($terms)) {
      $all_terms = ctools_break_phrase(implode($conf['concatenator'], $terms));
      return ctools_context_create('terms', $all_terms);
    }
  }
}

/**
 * Settings form for the relationship.
 */
function ctools_terms_from_node_settings_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $options = array();
  foreach (taxonomy_get_vocabularies() as $vid => $vocabulary) {
    $options[$vid] = $vocabulary->name;
  }
  $form['vid'] = array(
    '#title' => t('Vocabulary'),
    '#type' => 'checkboxes',
    '#options' => $options,
    '#default_value' => $conf['vid'],
    '#prefix' => '<div class="clearfix">',
    '#suffix' => '</div>',
  );
  $form['concatenator'] = array(
    '#title' => t('Concatenator'),
    '#type' => 'select',
    '#options' => array(',' => ', (AND)', '+' => '+ (OR)'),
    '#default_value' => $conf['concatenator'],
    '#prefix' => '<div class="clearfix">',
    '#suffix' => '</div>',
    '#description' => t("When the value from this context is passed on to a view as argument, the terms can be concatenated in the form of 1+2+3 (for OR) or 1,2,3 (for AND)."),
  );

  return $form;
}
