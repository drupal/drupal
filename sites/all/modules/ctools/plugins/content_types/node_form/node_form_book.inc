<?php
// $Id: node_form_book.inc,v 1.4 2010/10/11 22:18:23 sdboyer Exp $

if (module_exists('book')) {
  /**
   * Plugins are described by creating a $plugin array which will be used
   * by the system that includes this file.
   */
  $plugin = array(
    'single' => TRUE,
    'icon' => 'icon_node_form.png',
    'title' => t('Node form book options'),
    'description' => t('Book options for the node.'),
    'required context' => new ctools_context_required(t('Form'), 'node_form'),
    'category' => t('Form'),
  );
}

function ctools_node_form_book_content_type_render($subtype, $conf, $panel_args, &$context) {
  $block = new stdClass();
  $block->module = t('node_form');

  $block->title = t('Book options');
  $block->delta = 'book-options';

  if (isset($context->form)) {
    if (!empty($context->form['form_id'])) {
      $block->content = '';
      if ($context->form['parent']['#type'] != 'value') {
        $block->content .= drupal_render($context->form['parent']);
      }
      if ($context->form['weight']['#type'] != 'value') {
        $block->content .= drupal_render($context->form['weight']);
      }
    }
  }
  else {
    $block->content = t('Book options.');
  }
  return $block;
}

function ctools_node_form_book_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" node form book options', array('@s' => $context->identifier));
}

function ctools_node_form_book_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
  return $form;
}
