<?php
// $Id: contact.inc,v 1.4 2010/10/11 22:18:24 sdboyer Exp $

if (module_exists('contact')) {
  /**
   * Plugins are described by creating a $plugin array which will be used
   * by the system that includes this file.
   */
  $plugin = array(
    'single' => TRUE,
    'title' => t('Contact form'),
    'icon' => 'icon_contact.png',
    'description' => t('The site contact form that allows users to send a message to site administrators.'),
    'category' => t('Widgets'),
  );
}

/**
 * Render the custom content type.
 */
function ctools_contact_content_type_render($subtype, $conf, $panel_args, $context) {
  if (!user_access('access site-wide contact form')) {
    return;
  }
  // Build the content type block.
  $block = new stdClass();
  $block->module  = 'contact';
  $block->delta   = 'form';
  $block->title   = t('Contact');

  module_load_include('inc', 'contact', 'contact.pages');
  $block->content = contact_site_page();
  return $block;
}

/**
 * Returns an edit form for custom type settings.
 */
function ctools_contact_content_type_edit_form($form, &$form_state) {
  // Empty so that we can have title override.
  return $form;
}

/**
 * Submit handler for contact form.
 */
function ctools_contact_content_type_edit_form_submit($form, &$form_state) {
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
function ctools_contact_content_type_admin_title($subtype, $conf, $context) {
  return t('Contact form');
}
