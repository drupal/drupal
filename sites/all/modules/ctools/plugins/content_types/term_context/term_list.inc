<?php
// $Id: term_list.inc,v 1.8 2010/10/11 22:18:23 sdboyer Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'title' => t('List of related terms'),
  'icon' => 'icon_term.png',
  'description' => t('Terms related to an existing term; may be child, siblings or top level.'),
  'required context' => new ctools_context_required(t('Term'), 'term'),
  'category' => t('Taxonomy term'),
  'defaults' => array('title' => '', 'type' => 'child', 'list_type' => 'ul'),
);

function ctools_term_list_content_type_render($subtype, $conf, $panel_args, $context) {
  $term = isset($context->data) ? clone($context->data) : NULL;
  $block = new stdClass();
  $block->module = 'term-list';

  $options = ctools_admin_term_list_options();
  if ($term) {
    $block->subject = $options[$conf['type']];
    $block->delta = $conf['type'];
    switch ($conf['type']) {
      case 'related':
        // FIXME this no longer exists, must be done with Field API
        // $terms = taxonomy_get_related($term->tid);
        break;

      case 'child':
      default:
        $terms = taxonomy_get_children($term->tid);
        break;

      case 'top':
        $terms = taxonomy_get_children(0, $term->vid);
        break;

      case 'sibling':
        $parent = db_query('SELECT parent FROM {term_hierarchy} WHERE tid = :tid', array(':tid' => $term->tid))->fetchField();
        $terms = taxonomy_get_children($parent, $term->vid);
        // Remove the term that started this.
        unset($terms[$term->tid]);
        break;

      case 'synonyms':
        $terms = taxonomy_get_synonyms($term->tid);
        break;
    }
    if ($terms) {
      foreach ($terms as $related) {
        if (is_object($related)) {
          $items[] = l($related->name, taxonomy_term_path($related), array('rel' => 'tag', 'title' => strip_tags($related->description)));
        }
        else {
          $items[] = check_plain($related);
        }
      }

      $block->content = theme('item_list', $items, NULL, $conf['list_type']);
    }
  }
  else {
    $block->content = t('Term description goes here.');
    $block->delta = 'unknown';
  }

  return $block;
}

function ctools_admin_term_list_options() {
  return array(
    'child' => t('Child terms'),
    'related' => t('Related terms'),
    'sibling' => t('Sibling terms'),
    'top' => t('Top level terms'),
    'synonyms' => t('Term synonyms'),
  );
}

/**
 * Returns an edit form for the custom type.
 */
function ctools_term_list_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $form['type'] = array(
    '#type' => 'radios',
    '#title' => t('Which terms'),
    '#options' => ctools_admin_term_list_options(),
    '#default_value' => $conf['type'],
    '#prefix' => '<div class="clearfix no-float">',
    '#suffix' => '</div>',
  );

  $form['list_type'] = array(
    '#type' => 'select',
    '#title' => t('List type'),
    '#options' => array('ul' => t('Unordered'), 'ol' => t('Ordered')),
    '#default_value' => $conf['list_type'],
  );
  return $form;
}

function ctools_term_list_content_type_admin_title($subtype, $conf, $context) {
  $options = ctools_admin_term_list_options();
  return t('"@s" @type', array('@s' => $context->identifier, '@type' => drupal_strtolower($options[$conf['type']])));
}

function ctools_term_list_content_type_edit_form_submit($form, &$form_state) {
  // Copy everything from our defaults.
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
}

