<?php
// $Id: term_from_node.inc,v 1.6 2010/10/11 22:18:23 sdboyer Exp $

/**
 * @file
 * Plugin to provide an relationship handler for term from node.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Term from node'),
  'keyword' => 'term',
  'description' => t('Adds a taxonomy term from a node context; if multiple terms are selected, this will get the "first" term only.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'context' => 'ctools_term_from_node_context',
  'edit form' => 'ctools_term_from_node_settings_form',
  'defaults' => array('vid' => ''),
);

/**
 * Return a new context based on an existing context.
 */
function ctools_term_from_node_context($context, $conf) {
  // If unset it wants a generic, unfilled context, which is just NULL.
  if (empty($context->data)) {
    return ctools_context_create_empty('term', NULL);
  }

  if (isset($context->data->taxonomy)) {
    foreach ($context->data->taxonomy as $term) {
      if ($term->vid == $conf['vid']) {
        return ctools_context_create('term', $term);
      }
    }
  }
}

/**
 * Settings form for the relationship.
 */
function ctools_term_from_node_settings_form($form, &$form_state) {
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
    '#prefix' => '<div class="clearfix">',
    '#suffix' => '</div>',
  );

  return $form;
}
