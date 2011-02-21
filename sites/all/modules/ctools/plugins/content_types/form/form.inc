<?php
// $Id: form.inc,v 1.3 2010/10/11 22:18:24 sdboyer Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  // only provides a single content type
  'single' => TRUE,
  'render last' => TRUE,
  'title' => t('General form'),
  'icon' => 'icon_form.png',
  'description' => t('Everything in the form that is not displayed by other content.'),
  'required context' => new ctools_context_required(t('Form'), 'form'),
  'category' => t('Form'),
);

/**
 * Output function for the 'node' content type. Outputs a node
 * based on the module and delta supplied in the configuration.
 */
function ctools_form_content_type_render($subtype, $conf, $panel_args, &$context) {
  $block = new stdClass();
  $block->module = 'form';

  if (isset($context->form)) {
    $block->title = $context->form_title;
    if (!empty($context->form_id)) {
      // If this is a form, drupal_render it.
      $block->content = drupal_render($context->form);
    }
    else {
      // Otherwise just spit back what we were given. This is probably an
      // error message or something.
      $block->content = $context->form;
    }
    $block->delta = $context->form_id;
  }
  else {
    $block->title = t('Form');
    $block->content = t('Form goes here.');
    $block->delta   = 'unknown';
  }

  return $block;
}

function ctools_form_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" base form', array('@s' => $context->identifier));
}

function ctools_form_content_type_edit_form($form, &$form_state) {
  // provide a blank form so we have a place to override title
  // and stuff.
  return $form;
}
