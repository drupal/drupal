<?php
// $Id: node_links.inc,v 1.6 2010/12/31 21:06:18 merlinofchaos Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'title' => t('Node links'),
  'icon' => 'icon_node.png',
  'description' => t('Node links of the referenced node.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'category' => t('Node'),
  'defaults' => array(
    'override_title' => FALSE,
    'override_title_text' => '',
    'build_mode' => '',
    'identifier' => '',
    'link' => TRUE,
  ),
);

/**
 * Output function for the 'node' content type. Outputs a node
 * based on the module and delta supplied in the configuration.
 */
function ctools_node_links_content_type_render($subtype, $conf, $panel_args, $context) {
  if (!empty($context) && empty($context->data)) {
    return;
  }

  $node = isset($context->data) ? clone($context->data) : NULL;
  $block = new stdClass();
  $block->module = 'node';
  $block->delta  = $node->nid;

  if (empty($node)) {
    $block->delta   = 'placeholder';
    $block->subject = t('Node title.');
    $block->content = t('Node links go here.');
  }
  else {
    if (!empty($conf['identifier'])) {
      $node->panel_identifier = $conf['identifier'];
    }

    $block->subject = $node->title;
    node_build_content($node, $conf['build_mode']);
    $block->content = $node->content['links'];
  }

  if (!empty($conf['link']) && $node) {
    $block->title_link = "node/$node->nid";
  }
  return $block;
}

/**
 * Returns an edit form for the custom type.
 */
function ctools_node_links_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $form['link'] = array(
    '#title' => t('Link title to node'),
    '#type' => 'checkbox',
    '#default_value' => $conf['link'],
    '#description' => t('Check here to make the title link to the node.'),
  );

  $entity = entity_get_info('node');
  $build_mode_options = array();
  foreach ($entity['view modes'] as $mode => $option) {
    $build_mode_options[$mode] = $option['label'];
  }

  $form['build_mode'] = array(
    '#title' => t('Build mode'),
    '#type' => 'select',
    '#description' => t('Select a build mode for this node.'),
    '#options' => $build_mode_options,
    '#default_value' => $conf['build_mode'],
  );

  $form['identifier'] = array(
    '#type' => 'textfield',
    '#default_value' => $conf['identifier'],
    '#title' => t('Identifier'),
    '#description' => t('Whatever is placed here will appear in $node->panel_identifier to help theme node links displayed on the panel'),
  );

  return $form;
}

function ctools_node_links_content_type_edit_form_submit($form, &$form_state) {
  // Copy everything from our defaults.
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
}

function ctools_node_links_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" links', array('@s' => $context->identifier));
}

