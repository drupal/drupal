<?php
// $Id: node_content.inc,v 1.9 2010/12/31 21:05:24 merlinofchaos Exp $

/**
 * Plugins are described by creating a $plugin array which will be used
 * by the system that includes this file.
 */
$plugin = array(
  'single' => TRUE,
  'title' => t('Node content'),
  'icon' => 'icon_node.png',
  'description' => t('The content of the referenced node.'),
  'required context' => new ctools_context_required(t('Node'), 'node'),
  'category' => t('Node'),
  'defaults' => array(
    'links' => TRUE,
    'no_extras' => TRUE,
    'override_title' => FALSE,
    'override_title_text' => '',
    'identifier' => '',
    'link' => TRUE,
    'leave_node_title' => FALSE,
    'build_mode' => 'teaser',
  ),
);

/**
 * Render the node content.
 */
function ctools_node_content_content_type_render($subtype, $conf, $panel_args, $context) {
  if (!empty($context) && empty($context->data)) {
    return;
  }

  $node = isset($context->data) ? clone($context->data) : NULL;
  $block = new stdClass();
  $block->module = 'node';
  $block->delta  = $node->nid;

  if (empty($node)) {
    $block->delta   = 'placeholder';
    $block->title = t('Node title.');
    $block->content = t('Node content goes here.');
  }
  else {
    if (!empty($conf['identifier'])) {
      $node->panel_identifier = $conf['identifier'];
    }

    $block->title = $node->title;
    if (empty($conf['leave_node_title'])) {
      $node->title = NULL;
    }
    $block->content = ctools_node_content_render_node($node, $conf);
  }

  if (!empty($conf['link']) && $node) {
    $block->title_link = "node/$node->nid";
  }

  return $block;
}

function ctools_node_content_render_node($node, $conf) {
  if (empty($node->content)) {
    // Copied from node_build_content() so we can fiddle with it as we render.
    $node->content = array();

    // The 'view' hook can be implemented to overwrite the default function
    // to display nodes.
    if (node_hook($node, 'view')) {
      $node = node_invoke($node, 'view', $conf['build_mode']);
    }

    // Build fields content.
    // In case of a multiple view, node_view_multiple() already ran the
    // 'prepare_view' step. An internal flag prevents the operation from running
    // twice.
    field_attach_prepare_view('node', array($node->nid => $node), $conf['build_mode']);
    entity_prepare_view('node', array($node->nid => $node));
    $node->content += field_attach_view('node', $node, $conf['build_mode']);

    // Always display a read more link on teasers because we have no way
    // to know when a teaser view is different than a full view.
    $links = array();
    if ($conf['build_mode'] == 'teaser') {
      $links['node-readmore'] = array(
        'title' => t('Read more'),
        'href' => 'node/' . $node->nid,
        'attributes' => array('rel' => 'tag', 'title' => strip_tags($node->title))
      );
    }

    $node->content['links'] = array(
      '#theme' => 'links__node',
      '#links' => $links,
      '#attributes' => array('class' => array('links', 'inline')),
    );

    if (empty($conf['no_extras'])) {
      // Allow modules to make their own additions to the node.
      module_invoke_all('node_view', $node, $conf['build_mode']);
      module_invoke_all('entity_view', $node, 'node', $conf['build_mode']);
    }
  }

  // Set the proper node part, then unset unused $node part so that a bad
  // theme can not open a security hole.
  $content = $node->content;

  $content += array(
    '#theme' => 'node',
    '#node' => $node,
    '#view_mode' => $conf['build_mode'],
    '#language' => NULL,
  );

  // Add contextual links for this node, except when the node is already being
  // displayed on its own page. Modules may alter this behavior (for example,
  // to restrict contextual links to certain view modes) by implementing
  // hook_node_view_alter().
  if (!empty($node->nid) && !($conf['build_mode'] == 'full' && node_is_page($node))) {
    $content['#contextual_links']['node'] = array('node', array($node->nid));
  }

  // Allow modules to modify the structured node.
  $type = 'node';
  drupal_alter(array('node_view', 'entity_view'), $content, $type);

  // Kill the links if not requested.
  if (!$conf['links']) {
    $content['links']['#access'] = FALSE;
  }

  return $content;
}

/**
 * Returns an edit form for the custom type.
 */
function ctools_node_content_content_type_edit_form($form, &$form_state) {
  $conf = $form_state['conf'];

  $form['leave_node_title'] = array(
    '#type' => 'checkbox',
    '#default_value' => !empty($conf['leave_node_title']),
    '#title' => t('Leave node title'),
    '#description' => t('Advanced: if checked, do not touch the node title; this can cause the node title to appear twice unless your theme is aware of this.'),
  );

  $form['link'] = array(
    '#title' => t('Link title to node'),
    '#type' => 'checkbox',
    '#default_value' => $conf['link'],
    '#description' => t('Check here to make the title link to the node.'),
  );
  $form['links'] = array(
    '#type' => 'checkbox',
    '#default_value' => $conf['links'],
    '#title' => t('Include node links for "add comment", "read more" etc.'),
  );

  $form['no_extras'] = array(
    '#type' => 'checkbox',
    '#default_value' => $conf['no_extras'],
    '#title' => t('No extras'),
    '#description' => t('Check here to disable additions that modules might make to the node, such as file attachments and CCK fields; this should just display the basic teaser or body.'),
  );

  $form['identifier'] = array(
    '#type' => 'textfield',
    '#default_value' => $conf['identifier'],
    '#title' => t('Template identifier'),
    '#description' => t('This identifier will be added as a template suggestion to display this node: node-panel-IDENTIFIER.tpl.php. Please see the Drupal theming guide for information about template suggestions.'),
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

  return $form;
}

function ctools_node_content_content_type_edit_form_submit($form, &$form_state) {
  // Copy everything from our defaults.
  foreach (array_keys($form_state['plugin']['defaults']) as $key) {
    $form_state['conf'][$key] = $form_state['values'][$key];
  }
}

function ctools_node_content_content_type_admin_title($subtype, $conf, $context) {
  return t('"@s" content', array('@s' => $context->identifier));
}

