<?php
// $Id: upload.views.inc,v 1.16.4.5 2010/07/19 09:18:42 dereine Exp $
/**
 * @file
 *
 * Provide views data and handlers for upload tables that are not represented by
 * their own module.
 */

/**
 * @defgroup views_upload_module upload.module handlers
 *
 * @{
 */

/**
 * Implements hook_views_data()
 */
function upload_views_data() {
  $data = array();

  // ----------------------------------------------------------------------
  // upload table

  $data['upload']['table']['group']  = t('Upload');

  $data['upload']['table']['join'] = array(
    'node' => array(
      'left_field' => 'vid',
      'field' => 'vid',
    ),
    'node_revisions' => array(
      'left_field' => 'vid',
      'field' => 'vid',
    ),
    'file_managed' => array(
      'left_field' => 'fid',
      'field' => 'fid',
    ),
  );

  $data['upload']['vid'] = array(
    'title' => t('Node'),
    'help' => t('The node the uploaded file is attached to'),
    'relationship' => array(
      'label' => t('upload'),
      'base' => 'node',
      'base field' => 'vid',
      // This allows us to not show this relationship if the base is already
      // node so users won't create circular relationships.
      'skip base' => array('node', 'node_revisions'),
    ),
  );

  $data['upload']['description'] = array(
    'title' => t('Description'),
    'help' => t('The description of the uploaded file.'),
    'field' => array(
      'handler' => 'views_handler_field_upload_description',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  $data['upload']['list'] = array(
    'title' => t('Listed'),
    'help' => t('Whether or not the file is marked to be listed.'),
    'field' => array(
      'handler' => 'views_handler_field_boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_boolean_operator',
      'label' => t('Published'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  $data['upload']['weight'] = array(
    'title' => t('Weight'),
    'help' => t('The weight, used for sorting.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_data_alter()
 */
function upload_views_data_alter(&$data) {
  $data['node']['upload_fid'] = array(
    'group' => t('Upload'),
    'title' => t('Attached files'),
    'help' => t('All files attached to a node with upload.module.'),
    'real field' => 'vid',
    'field' => array(
      'handler' => 'views_handler_field_upload_fid',
      'no group by' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_upload_fid',
      'title' => t('Has attached files'),
      'type' => 'yes-no',
      'help' => t('Only display items with attached files. This can cause duplicates if there are multiple attached files.'),
    ),
    'relationship' => array(
      'title' => t('Attached files'),
      'help' => t('Add a relationship to gain access to more file data for files uploaded by upload.module. Note that this relationship will cause duplicate nodes if there are multiple files attached to the node.'),
      'relationship table' => 'upload',
      'relationship field' => 'fid',
      'base' => 'file_managed',
      'base field' => 'fid',
      'handler' => 'views_handler_relationship',
      'label' => t('Files'),
    ),
  );
}

/**
 * @}
 */
