<?php
// $Id: system.views.inc,v 1.7.6.7 2010/10/16 09:06:51 dereine Exp $
/**
 * @file
 *
 * Provide views data and handlers for system tables that are not represented by
 * their own module.
 */

/**
 * @defgroup views_system_module system.module handlers
 *
 * @{
 */

/**
 * Implements hook_views_data()
 */
function system_views_data() {
  $data = array();

  // ----------------------------------------------------------------------
  // file_managed table

  $data['file_managed']['table']['group']  = t('File');

  // Advertise this table as a possible base table
  $data['file_managed']['table']['base'] = array(
    'field' => 'fid',
    'title' => t('File'),
    'help' => t("Files maintained by Drupal and various modules."),
  );

  // The file table does not inherently join to the node table,
  // but may things (such as upload.module) can add relationships
  // that allow file fields to be used.

  // For other base tables, explain how we join
  $data['file_managed']['table']['join'] = array(
    'users' => array(
      // direct join to the users table via 'uid' field.
      'left_field' => 'uid',
      'field' => 'uid',
     ),
  );

  // fid
  $data['file_managed']['fid'] = array(
    'title' => t('File ID'),
    'help' => t('The ID of the file.'),
    'field' => array(
      'handler' => 'views_handler_field_file',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_file_fid',
      'name field' => 'filename', // the field to display in the summary.
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // filename
  $data['file_managed']['filename'] = array(
    'title' => t('Name'),
    'help' => t('The name of the file.'),
    'field' => array(
      'handler' => 'views_handler_field_file',
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

  // uri
  $data['file_managed']['uri'] = array(
    'title' => t('Path'),
    'help' => t('The path of the file.'),
    'field' => array(
      'handler' => 'views_handler_field_file_uri',
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

  // filemime
  $data['file_managed']['filemime'] = array(
    'title' => t('Mime type'),
    'help' => t('The mime type of the file.'),
    'field' => array(
      'handler' => 'views_handler_field_file_filemime',
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

  // extension
  $data['files']['extension'] = array(
    'title' => t('Extension'),
    'help' => t('The extension of the file.'),
    'real field' => 'filename',
    'field' => array(
      'handler' => 'views_handler_field_file_extension',
      'click sortable' => FALSE,
     ),
  );

  // filesize
  $data['file_managed']['filesize'] = array(
    'title' => t('Size'),
    'help' => t('The size of the file.'),
    'field' => array(
      'handler' => 'views_handler_field_file_size',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
  );

  // status
  $data['file_managed']['status'] = array(
    'title' => t('Status'),
    'help' => t('The status of the file.'),
    'field' => array(
      'handler' => 'views_handler_field_file_status',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_file_status',
    ),
  );

  // timestamp field
  $data['file_managed']['timestamp'] = array(
    'title' => t('Upload date'),
    'help' => t('The date the file was uploaded.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );

  // ----------------------------------------------------------------------
  // file_usage table

  $data['file_usage']['table']['group']  = t('File Usage');

  // Provide field-type-things to several base tables; on the core files table ("file_managed") so
  // that we can create relationships from files to entities, and then on each core entity type base
  // table so that we can provide general relationships between entities and files.
  $data['file_usage']['table']['join'] = array(
    // Link ourself to the {file_managed} table so we can provide file->entity relationships.
    'file_managed' => array(
      'field' => 'fid',
      'left_field' => 'fid',
    ),
    // Link ourself to the {node} table so we can provide node->file relationships.
    'node' => array(
      'field' => 'id',
      'left_field' => 'nid',
      'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'value' => 'node')),
    ),
    // Link ourself to the {users} table so we can provide user->file relationships.
    'users' => array(
      'field' => 'id',
      'left_field' => 'uid',
      'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'value' => 'user')),
    ),
    // Link ourself to the {comment} table so we can provide comment->file relationships.
    'comment' => array(
      'field' => 'id',
      'left_field' => 'cid',
      'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'value' => 'comment')),
    ),
    // Link ourself to the {taxonomy_term_data} table so we can provide taxonomy_term->file relationships.
    'taxonomy_term_data' => array(
      'field' => 'id',
      'left_field' => 'tid',
      'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'value' => 'taxonomy_term')),
    ),
    // Link ourself to the {taxonomy_vocabulary} table so we can provide taxonomy_vocabulary->file relationships.
    'taxonomy_vocabulary' => array(
      'field' => 'id',
      'left_field' => 'vid',
      'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'value' => 'taxonomy_vocabulary')),
    ),
  );

  // Provide a relationship between the files table and each entity type, and between each entity
  // type and the files table. Entity->file relationships are type-restricted in the joins
  // declared above, and file->entity relationships are type-restricted in the relationship
  // declarations below.

  // Relationships between files and nodes.
  $data['file_usage']['file_to_node'] = array(
    'title' => t('Node'),
    'help' => t('A node that is associated with this file, usually because this file is in a field on the node.'),
    // Only provide this field/relationship/etc. when the 'file_managed' base table is present.
    'skip base' => array('node', 'node_revision', 'users', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
    'real field' => 'id',
    'relationship' => array(
      'title' => t('Node'),
      'label' => t('Node'),
      'base' => 'node',
      'base field' => 'nid',
      'relationship field' => 'id',
      'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'operator' => '=', 'value' => 'node')),
    ),
  );
  $data['file_usage']['node_to_file'] = array(
    'title' => t('File'),
    'help' => t('A file that is associated with this node, usually because it is in a field on the node.'),
    // Only provide this field/relationship/etc. when the 'node' base table is present.
    'skip base' => array('file_managed', 'users', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
    'real field' => 'fid',
    'relationship' => array(
      'title' => t('File'),
      'label' => t('File'),
      'base' => 'file_managed',
      'base field' => 'fid',
      'relationship field' => 'fid',
    ),
  );

  // Relationships between files and users.
  $data['file_usage']['file_to_user'] = array(
    'title' => t('User'),
    'help' => t('A user that is associated with this file, usually because this file is in a field on the user.'),
    // Only provide this field/relationship/etc. when the 'file_managed' base table is present.
    'skip base' => array('node', 'node_revision', 'users', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
    'real field' => 'id',
    'relationship' => array(
      'title' => t('User'),
      'label' => t('User'),
      'base' => 'users',
      'base field' => 'uid',
      'relationship field' => 'id',
      'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'operator' => '=', 'value' => 'user')),
    ),
  );
  $data['file_usage']['user_to_file'] = array(
    'title' => t('File'),
    'help' => t('A file that is associated with this user, usually because it is in a field on the user.'),
    // Only provide this field/relationship/etc. when the 'users' base table is present.
    'skip base' => array('file_managed', 'node', 'node_revision', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
    'real field' => 'fid',
    'relationship' => array(
      'title' => t('File'),
      'label' => t('File'),
      'base' => 'file_managed',
      'base field' => 'fid',
      'relationship field' => 'fid',
    ),
  );

  // Relationships between files and comments.
  $data['file_usage']['file_to_comment'] = array(
    'title' => t('Comment'),
    'help' => t('A comment that is associated with this file, usually because this file is in a field on the comment.'),
    // Only provide this field/relationship/etc. when the 'file_managed' base table is present.
    'skip base' => array('node', 'node_revision', 'users', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
    'real field' => 'id',
    'relationship' => array(
      'title' => t('Comment'),
      'label' => t('Comment'),
      'base' => 'comment',
      'base field' => 'cid',
      'relationship field' => 'id',
      'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'operator' => '=', 'value' => 'comment')),
    ),
  );
  $data['file_usage']['comment_to_file'] = array(
    'title' => t('File'),
    'help' => t('A file that is associated with this comment, usually because it is in a field on the comment.'),
    // Only provide this field/relationship/etc. when the 'comment' base table is present.
    'skip base' => array('file_managed', 'node', 'node_revision', 'users', 'taxonomy_term_data', 'taxonomy_vocabulary'),
    'real field' => 'fid',
    'relationship' => array(
      'title' => t('File'),
      'label' => t('File'),
      'base' => 'file_managed',
      'base field' => 'fid',
      'relationship field' => 'fid',
    ),
  );

  // Relationships between files and taxonomy_terms.
  $data['file_usage']['file_to_taxonomy_term'] = array(
    'title' => t('Taxonomy Term'),
    'help' => t('A taxonomy term that is associated with this file, usually because this file is in a field on the taxonomy term.'),
    // Only provide this field/relationship/etc. when the 'file_managed' base table is present.
    'skip base' => array('node', 'node_revision', 'users', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
    'real field' => 'id',
    'relationship' => array(
      'title' => t('Taxonomy Term'),
      'label' => t('Taxonomy Term'),
      'base' => 'taxonomy_term_data',
      'base field' => 'tid',
      'relationship field' => 'id',
      'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'operator' => '=', 'value' => 'taxonomy_term')),
    ),
  );
  $data['file_usage']['taxonomy_term_to_file'] = array(
    'title' => t('File'),
    'help' => t('A file that is associated with this taxonomy term, usually because it is in a field on the taxonomy term.'),
    // Only provide this field/relationship/etc. when the 'taxonomy_term_data' base table is present.
    'skip base' => array('file_managed', 'node', 'node_revision', 'users', 'comment', 'taxonomy_vocabulary'),
    'real field' => 'fid',
    'relationship' => array(
      'title' => t('File'),
      'label' => t('File'),
      'base' => 'file_managed',
      'base field' => 'fid',
      'relationship field' => 'fid',
    ),
  );

  // Relationships between files and taxonomy_vocabulary items.
  $data['file_usage']['file_to_taxonomy_vocabulary'] = array(
    'title' => t('Taxonomy Vocabulary'),
    'help' => t('A taxonomy vocabulary that is associated with this file, usually because this file is in a field on the taxonomy vocabulary.'),
    // Only provide this field/relationship/etc. when the 'file_managed' base table is present.
    'skip base' => array('node', 'node_revision', 'users', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
    'real field' => 'id',
    'relationship' => array(
      'title' => t('Taxonomy Vocabulary'),
      'label' => t('Taxonomy Vocabulary'),
      'base' => 'taxonomy_vocabulary',
      'base field' => 'vid',
      'relationship field' => 'id',
      'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'operator' => '=', 'value' => 'taxonomy_vocabulary')),
    ),
  );
  $data['file_usage']['taxonomy_vocabulary_to_file'] = array(
    'title' => t('File'),
    'help' => t('A file that is associated with this taxonomy vocabulary, usually because it is in a field on the taxonomy vocabulary.'),
    // Only provide this field/relationship/etc. when the 'taxonomy_vocabulary' base table is present.
    'skip base' => array('file_managed', 'node', 'node_revision', 'users', 'comment', 'taxonomy_term_data'),
    'real field' => 'fid',
    'relationship' => array(
      'title' => t('File'),
      'label' => t('File'),
      'base' => 'file_managed',
      'base field' => 'fid',
      'relationship field' => 'fid',
    ),
  );

  // Provide basic fields from the {file_usage} table to all of the base tables we've declared
  // joins to (because there is no 'skip base' property on these fields).
  $data['file_usage']['module'] = array(
    'title' => t('Module'),
    'help' => t('The module managing this file relationship.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['file_usage']['type'] = array(
    'title' => t('Entity type'),
    'help' => t('The type of entity that is related to the file.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );
  $data['file_usage']['count'] = array(
    'title' => t('Use count'),
    'help' => t('The number of times the file is used by this entity.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  return $data;
}

function _views_file_status($choice = NULL) {
  $status = array(
    0 => t('Temporary'),
    FILE_STATUS_PERMANENT => t('Permanent'),
  );

  if (isset($choice)) {
    return isset($status[$choice]) ? $status[$choice] : t('Unknown');
  }

  return $status;
}

  // uid field
  $data['files']['uid'] = array(
    'title' => t('User'),
    'help' => t('The user, who uploaded the file.'),
    'relationship' => array(
      'base' => 'users',
      'base field' => 'uid',
      'handler' => 'views_handler_relationship',
      'label' => t('User'),
    ),
  );


/**
 * @}
 */
