<?php

if (module_exists('book')) {
  /**
   * Plugins are described by creating a $plugin array which will be used
   * by the system that includes this file.
   */
  $plugin = array(
    'single' => TRUE,
    'title' => t('Book children'),
    'icon' => 'icon_node.png',
    'description' => t('The children menu the book the node belongs to.'),
    'required context' => new ctools_context_required(t('Node'), 'node'),
    'category' => t('Node'),
  );
}

function ctools_node_book_children_content_type_render($subtype, $conf, $panel_args, $context) {
  $node = isset($context->data) ? drupal_clone($context->data) : NULL;
  $block = new stdClass();
  $block->module = 'book_children';

  $block->title = t('Book children');
  if ($node) {
    $block->content = isset($node->book) ? book_children($node->book) : '';
    $block->delta = $node->nid;
  }
  else {
    $block->content = t('Book children menu goes here.');
    $block->delta = 'unknown';
  }

  return $block;
}

function ctools_node_book_children_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" book children', array('@s' => $context->identifier));
}

function ctools_node_book_children_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
  return $form;
}
