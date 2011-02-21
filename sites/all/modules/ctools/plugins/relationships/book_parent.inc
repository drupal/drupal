<?php
// $Id: book_parent.inc,v 1.7 2010/10/11 22:18:23 sdboyer Exp $

/**
 * @file
 * Plugin to provide an relationship handler for book parent.
 */

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'title' => t('Book parent'),
  'keyword' => 'book_parent',
  'description' => t('Adds a book parent from a node context.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'context' => 'ctools_book_parent_context',
  'edit form' => 'ctools_book_parent_settings_form',
  'defaults' => array('type' => 'top'),
);

/**
 * Return a new context based on an existing context.
 */
function ctools_book_parent_context($context, $conf) {
  // If unset it wants a generic, unfilled context, which is just NULL.
  if (empty($context->data)) {
    return ctools_context_create_empty('node');
  }

  if (isset($context->data->book)) {
    if ($conf['type'] == 'top') {
      $nid = $context->data->book['bid'];
    }
    else {
      // Just load the parent book.
      $item = book_link_load($context->data->book['plid']);
      $nid = $item['nid'];
    }

    if (!empty($nid)) {
      // Load the node.
      $node = node_load($nid);
      // Generate the context.
      return ctools_context_create('node', $node);
    }
  }
  else {
    return ctools_context_create_empty('node');
  }
}

/**
 * Settings form for the relationship.
 */
function ctools_book_parent_settings_form($form, &$form_state) {
  $conf = $form_state['conf'];
  $form['type'] = array(
    '#type' => 'select',
    '#title' => t('Relationship type'),
    '#options' => array('parent' => t('Immediate parent'), 'top' => t('Top level book')),
    '#default_value' => $conf['type'],
  );

  return $form;
}
