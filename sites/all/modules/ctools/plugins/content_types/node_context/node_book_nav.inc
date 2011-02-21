<?php
// $Id: node_book_nav.inc,v 1.4 2010/10/11 22:18:22 sdboyer Exp $

if (module_exists('book')) {
  /**
   * Plugins are described by creating a $plugin array which will be used
   * by the system that includes this file.
   */
  $plugin = array(
    'single' => TRUE,
    'title' => t('Book navigation'),
    'icon' => 'icon_node.png',
    'description' => t('The navigation menu the book the node belongs to.'),
    'required context' => new ctools_context_required(t('Node'), 'node'),
    'category' => t('Node'),
  );
}

function ctools_node_book_nav_content_type_render($subtype, $conf, $panel_args, $context) {
  $node = isset($context->data) ? clone($context->data) : NULL;
  $block = new stdClass();
  $block->module = 'book_nav';

  $block->title = t('Book navigation');
  if ($node) {
    $block->content = isset($node->book) ? theme('book_navigation', $node->book) : '';
    $block->delta = $node->nid;
  }
  else {
    $block->content = t('Book navigation goes here.');
    $block->delta = 'unknown';
  }

  return $block;
}

function ctools_node_book_nav_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" book navigation', array('@s' => $context->identifier));
}

function ctools_node_book_nav_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
  return $form;
}
