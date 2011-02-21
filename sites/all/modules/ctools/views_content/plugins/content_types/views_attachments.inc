<?php
// $Id: views_attachments.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Allow a view context to display its attachment(s).
 */

$plugin = array(
  'title' => t('View attachment'),
  'category' => t('View context'),
  'icon' => 'icon_views_page.png',
  'description' => t('Display the attachments on a view context.'),
  'required context' => new ctools_context_required(t('View'), 'view'),
  'defaults' => array(
    'which' => array(),
  ),
);

/**
 * Render the node_terms content type.
 */
function views_content_views_attachments_content_type_render($subtype, $conf, $panel_args, $context) {
  if (empty($context) || empty($context->data)) {
    return;
  }

  // Build the content type block.
  $block = new stdClass();
  $block->module  = 'views_attachments';
  $block->delta   = $context->argument;
  $block->title   = '';
  $block->content = '';

  $output = views_content_context_get_output($context);
  foreach ($conf['which'] as $attachment) {
    if (isset($output[$attachment])) {
      $block->content .= $output[$attachment];
    }
  }

  return $block;
}


function views_content_views_attachments_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $form['which'] = array(
    '#type' => 'checkboxes',
    '#options' => array(
      'attachment_before' => t('"Before" attachment'),
      'attachment_after' => t('"After" attachment'),
    ),
    '#default_value' => $conf['which'],
  );

  return $form;
}

function views_content_views_attachments_content_type_edit_form_validate(&$form, &$form_state) {
  if (!array_filter($form_state['values']['which'])) {
    form_error($form['which'], t('You must select at least one attachment to display.'));
  }
}

function views_content_views_attachments_content_type_edit_form_submit(&$form, &$form_state) {
  $form_state['conf']['which'] = array_filter($form_state['values']['which']);
}

/**
 * Returns the administrative title for a type.
 */
function views_content_views_attachments_content_type_admin_title($subtype, $conf, $context) {
  return t('"@context" attachment', array('@context' => $context->identifier));
}
