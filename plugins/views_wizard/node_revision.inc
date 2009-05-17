<?php

/**
 * @file
 * Views wizard for node revision views.
 */

$plugin = array(
  'name' => 'node_revision',
  'base_table' => 'node_revision',
  'created_column' => 'timestamp',
  'form_wizard_class' => array(
    'file' => 'views_ui_node_revision_views_wizard.class.php',
    'class' => 'ViewsUiNodeRevisionViewsWizard',
  ),
  'title' => t('Content revisions'),
  'filters' => array(
    'status' => array(
      'value' => '1',
      'table' => 'node', // @todo - unclear if this should be node or node_revision
      'field' => 'status',
    ),
  ),
  'path_field' => array(
    'id' => 'vid',
    'table' => 'node_revision',
    'field' => 'vid',
    'exclude' => TRUE,
    'alter' => array(
      'alter_text' => 1,
      'text' => 'node/[nid]/revisions/[vid]/view',
    ),
  ),
  'path_fields_supplemental' => array(
    array(
      'id' => 'nid',
      'table' => 'node',
      'field' => 'nid',
      'exclude' => TRUE,
      'link_to_node' => FALSE,
    ),
  ),
);
