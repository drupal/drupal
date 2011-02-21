<?php
// $Id: term.inc,v 1.12 2010/12/31 21:31:50 merlinofchaos Exp $

/**
 * @file
 *
 * Plugin to provide an argument handler for a Taxonomy term
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t("Taxonomy term: ID"),
  // keyword to use for %substitution
  'keyword' => 'term',
  'description' => t('Creates a single taxonomy term from a taxonomy ID or taxonomy term name.'),
  'context' => 'ctools_term_context',
  'default' => array('input_form' => 'tid', 'breadcrumb' => TRUE),
  'settings form' => 'ctools_term_settings_form',
  'placeholder form' => 'ctools_term_ctools_argument_placeholder',
  'breadcrumb' => 'ctools_term_breadcrumb',
);

/**
 * Discover if this argument gives us the term we crave.
 */
function ctools_term_context($arg = NULL, $conf = NULL, $empty = FALSE) {
  // If unset it wants a generic, unfilled context.
  if ($empty) {
    return ctools_context_create_empty('term');
  }

  switch ($conf['input_form']) {
    case 'tid':
    default:
      if (!is_numeric($arg)) {
        return FALSE;
      }
      $term = taxonomy_term_load($arg);
      break;

    case 'term':
      $terms = taxonomy_get_term_by_name($arg);

      $conf['vids'] = is_array($conf['vids']) ? array_filter($conf['vids']) : NULL;
      if ((count($terms) > 1) && isset($conf['vids'])) {
        foreach ($terms as $potential) {
          foreach ($conf['vids'] as $vid => $active) {
            if ($active && $potential->vid == $vid) {
              $term = $potential;
              // break out of the foreaches AND the case
              break 3;
            }
          }
        }
      }
      $term = array_shift($terms);
      break;
  }

  if (empty($term)) {
    return NULL;
  }

  if (!empty($conf['vids']) && array_filter($conf['vids']) && empty($conf['vids'][$term->vid])) {
    return NULL;
  }

  $context = ctools_context_create('term', $term);
  $context->original_argument = $arg;
  return $context;
}

/**
 * Settings form for the argument
 */
function ctools_term_settings_form($form, &$form_state, $conf) {
  // @todo allow synonym use like Views does.
  $form['settings']['input_form'] = array(
    '#title' => t('Argument type'),
    '#type' => 'radios',
    '#options' => array('tid' => t('Term ID'), 'term' => t('Term name')),
    '#default_value' => $conf['input_form'],
    '#prefix' => '<div class="clearfix">',
    '#suffix' => '</div>',
  );

  $vocabularies = taxonomy_get_vocabularies();
  $options = array();
  foreach ($vocabularies as $vid => $vocab) {
    $options[$vid] = $vocab->name;
  }
  $form['settings']['vids'] = array(
    '#title' => t('Limit to these vocabularies'),
    '#type' => 'checkboxes',
    '#options' => $options,
    '#default_value' => !empty($conf['vids']) ? $conf['vids'] : array(),
    '#description' => t('If no vocabularies are checked, terms from all vocabularies will be accepted.'),
  );

  $form['settings']['breadcrumb'] = array(
    '#title' => t('Inject hierarchy into breadcrumb trail'),
    '#type' => 'checkbox',
    '#default_value' => !empty($conf['breadcrumb']),
    '#description' => t('If checked, taxonomy term parents will appear in the breadcrumb trail.'),
  );
  return $form;
}

/**
 * Form fragment to get an argument to convert a placeholder for preview.
 */
function ctools_term_ctools_argument_placeholder($conf) {
  switch ($conf['input_form']) {
    case 'tid':
    default:
      return array(
        '#type' => 'textfield',
        '#description' => t('Enter a taxonomy term ID.'),
      );
    case 'term':
      return array(
        '#type' => 'textfield',
        '#description' => t('Enter a taxonomy term name.'),
      );
  }
}

/**
 * Inject the breadcrumb trail if necessary.
 */
function ctools_term_breadcrumb($conf, $context) {
  if (empty($conf['breadcrumb'])) {
    return;
  }

  $breadcrumb = array();
  $current->tid = $context->data->tid;
  while ($parents = taxonomy_get_parents($current->tid)) {
    $current = array_shift($parents);
    $breadcrumb[] = l($current->name, 'taxonomy/term/' . $current->tid);
  }

  $breadcrumb = array_merge(drupal_get_breadcrumb(), array_reverse($breadcrumb));
  drupal_set_breadcrumb($breadcrumb);
}
