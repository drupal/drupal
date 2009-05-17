<?php

/**
 * @file
 * Views wizard for node views.
 */

$plugin = array(
  'name' => 'node',
  'base_table' => 'node',
  'created_column' => 'created',
  'available_sorts' => array(
    'title:DESC' => t('Title')
  ),
  'form_wizard_class' => array(
    'file' => 'views_ui_node_views_wizard.class.php',
    'class' => 'ViewsUiNodeViewsWizard',
  ),
  'title' => t('Content'),
  'filters' => array(
    'status' => array(
      'value' => NODE_PUBLISHED,
      'table' => 'node',
      'field' => 'status',
    ),
  ),
  'path_field' => array(
    'id' => 'nid',
    'table' => 'node',
    'field' => 'nid',
    'exclude' => TRUE,
    'link_to_node' => FALSE,
    'alter' => array(
      'alter_text' => 1,
      'text' => 'node/[nid]',
    ),
  ),
);

if (module_exists('statistics')) {
  $plugin['available_sorts']['node_counter-totalcount:DESC'] = t('Number of hits');
}
