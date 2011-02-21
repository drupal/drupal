<?php
// $Id: node_form_buttons.inc,v 1.4 2010/10/11 22:18:23 sdboyer Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'icon' => 'icon_node_form.png',
  'title' => t('Node form submit buttons'),
  'description' => t('Submit buttons for the node form.'),
  'required context' => new ctools_context_required(t('Form'), 'node_form'),
  'category' => t('Form'),
);

function ctools_node_form_buttons_content_type_render($subtype, $conf, $panel_args, &$context) {
  $block = new stdClass();
  $block->module = t('node_form');

  $block->title = '';
  $block->delta = 'buttons';

  if (isset($context->form)) {
    $block->content = drupal_render($context->form['buttons']);
    $block->content .= drupal_render($context->form['form_token']);
    $block->content .= drupal_render($context->form['form_build_id']);
    $block->content .= drupal_render($context->form['form_id']);
  }
  else {
    $block->content = t('Node form buttons.');
  }
  return $block;
}

function ctools_node_form_buttons_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" node form submit buttons', array('@s' => $context->identifier));
}

function ctools_node_form_buttons_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
  return $form;
}
