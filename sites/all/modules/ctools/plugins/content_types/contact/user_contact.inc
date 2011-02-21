<?php
// $Id: user_contact.inc,v 1.3 2010/01/29 20:18:01 merlinofchaos Exp $

if (module_exists('contact')) {
  /**
   * Plugins are described by creating a $plugin array which will be used
   * by the system that includes this file.
   */
  $plugin = array(
    'single' => TRUE,
    'title' => t('User contact form'),
    'icon' => 'icon_contact.png',
    'description' => t('The site contact form that allows users to contact other users.'),
    'category' => t('User'),
    'required context' => new ctools_context_required(t('User'), 'user'),
  );
}

/**
 * Render the custom content type.
 */
function ctools_user_contact_content_type_render($subtype, $conf, $panel_args, $context) {
  if (empty($context) || empty($context->data)) {
    return;
  }

  if (!_contact_user_tab_access($context->data)) {
    return;
  }

  // Build the content type block.
  $block = new stdClass();
  $block->module  = 'contact';
  $block->delta   = 'form';
  $block->title   = t('Contact @name', array('@name' => $context->data->name));

  module_load_include('inc', 'contact', 'contact.pages');
  $block->content = contact_user_page($context->data);
  return $block;
}

/**
 * Returns an edit form for custom type settings.
 */
function ctools_user_contact_content_type_edit_form(&$form, &$form_state) {
  // Empty so that we can have title override.
}

/**
 * Submit handler for contact form.
 */
function ctools_user_contact_content_type_edit_form_submit(&$form, &$form_state) {
  // Copy everything from our defaults.
/*
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
*/
}

/**
 * Returns the administrative title for a type.
 */
function ctools_user_contact_content_type_admin_title($subtype, $conf, $context) {
  return t('User contact form');
}
