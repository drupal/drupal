<?php
// $Id: node_form_author.inc,v 1.4 2010/10/11 22:18:23 sdboyer Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'icon' => 'icon_node_form.png',
  'title' => t('Node form author information'),
  'description' => t('Author information on the Node form.'),
  'required context' => new ctools_context_required(t('Form'), 'node_form'),
  'category' => t('Form'),
);

function ctools_node_form_author_content_type_render($subtype, $conf, $panel_args, &$context) {
  $block = new stdClass();
  $block->module = t('node_form');

  $block->title = t('Authoring information');
  $block->delta = 'author-options';

  if (isset($context->form)) {
    if (!empty($context->form['form_id']) && !empty($context->form['author']['#access'])) {
      // remove the fieldset
      unset($context->form['author']['#type']);
      $context->form['author']['name']['#size'] /= 2;
      $context->form['author']['date']['#size'] /= 2;
      $block->content = drupal_render($context->form['author']);
    }
  }
  else {
    $block->content = t('Authoring information.');
  }
  return $block;
}

function ctools_node_form_author_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" node form publishing options', array('@s' => $context->identifier));
}

function ctools_node_form_author_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to have context setting.
  return $form;
}
