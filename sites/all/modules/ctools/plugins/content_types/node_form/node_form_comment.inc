<?php
// $Id: node_form_comment.inc,v 1.4 2010/10/11 22:18:23 sdboyer Exp $

if (module_exists('comment')) {
  /**
   * Plugins are described by creating a $plugin array which will be used
   * by the system that includes this file.
   */
  $plugin = array(
    'single' => TRUE,
    'icon' => 'icon_node_form.png',
    'title' => t('Node form comment settings'),
    'description' => t('Comment settings on the Node form.'),
    'required context' => new ctools_context_required(t('Form'), 'node_form'),
    'category' => t('Form'),
  );
}

function ctools_node_form_comment_content_type_render($subtype, $conf, $panel_args, &$context) {
  $block = new stdClass();
  $block->module = t('node_form');

  $block->title = t('Comment options');
  $block->delta = 'comment-options';

  if (isset($context->form)) {
    if (!empty($context->form['form_id']) && !empty($context->form['comment_settings']['#access'])) {
      // remove the fieldset
      unset($context->form['comment_settings']['#type']);
      $block->content = drupal_render($context->form['comment_settings']);
    }
  }
  else {
    $block->content = t('Comment options.');
  }
  return $block;
}

function ctools_node_form_comment_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" node form comment settings', array('@s' => $context->identifier));
}

function ctools_node_form_comment_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
  return $form;
}
