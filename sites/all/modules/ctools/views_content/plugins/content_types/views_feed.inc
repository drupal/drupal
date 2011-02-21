<?php
// $Id: views_feed.inc,v 1.2 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 * Allow a view context to display its attachment(s).
 */

$plugin = array(
  'title' => t('View feed icon'),
  'category' => t('View context'),
  'icon' => 'icon_views_page.png',
  'description' => t('Display the view feed icon if there are no results.'),
  'required context' => new ctools_context_required(t('View'), 'view'),
  'defaults' => array(
    'which' => array(),
  ),
);

/**
 * Render the node_terms content type.
 */
function views_content_views_feed_content_type_render($subtype, $conf, $panel_args, $context) {
  if (empty($context) || empty($context->data)) {
    return;
  }

  // Build the content type block.
  $block = new stdClass();
  $block->module  = 'views_feed';
  $block->delta   = $context->argument;
  $block->title   = '';
  $block->content = '';

  $output = views_content_context_get_output($context);
  $block->content = $output['feed_icon'];

  return $block;
}

function views_content_views_feed_content_type_edit_form($form, &$form_state) {
}

function views_content_views_feed_content_type_edit_form_submit(&$form, &$form_state) {
  // Kept so we guarantee we have a submit handler.
}

/**
 * Returns the administrative title for a type.
 */
function views_content_views_feed_content_type_admin_title($subtype, $conf, $context) {
  return t('"@context" feed icon', array('@context' => $context->identifier));
}
