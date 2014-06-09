<?php

/**
 * @file
 * Provide views data for file.module.
 */

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\field\FieldConfigInterface;

/**
 * Implements hook_views_data().
 */
function file_views_data() {
  $data = array();

  // Sets 'group' index for file_managed table.
  $data['file_managed']['table']['group']  = t('File');

  // Advertise this table as a possible base table.
  $data['file_managed']['table']['base'] = array(
    'field' => 'fid',
    'title' => t('File'),
    'help' => t("Files maintained by Drupal and various modules."),
    'defaults' => array(
      'field' => 'filename'
    ),
  );
  $data['file_managed']['table']['entity type'] = 'file';
  $data['file_managed']['table']['wizard_id'] = 'file_managed';

  // Describes fid field in file_managed table.
  $data['file_managed']['fid'] = array(
    'title' => t('File ID'),
    'help' => t('The ID of the file.'),
    'field' => array(
      'id' => 'file',
    ),
    'argument' => array(
      'id' => 'file_fid',
      // The field to display in the summary.
      'name field' => 'filename',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'title' => t('File usage'),
      'help' => t('Relate file entities to their usage.'),
      'id' => 'standard',
      'base' => 'file_usage',
      'base field' => 'fid',
      'field' => 'fid',
      'label' => t('File usage'),
    ),
  );

  // Describes filename field in file_managed table.
  $data['file_managed']['filename'] = array(
    'title' => t('Name'),
    'help' => t('The name of the file.'),
    'field' => array(
      'id' => 'file',
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // Describes uri field in file_managed table.
  $data['file_managed']['uri'] = array(
    'title' => t('Path'),
    'help' => t('The path of the file.'),
    'field' => array(
      'id' => 'file_uri',
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // Describes filemime field in file_managed table.
  $data['file_managed']['filemime'] = array(
    'title' => t('Mime type'),
    'help' => t('The mime type of the file.'),
    'field' => array(
      'id' => 'file_filemime',
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // Describes extension field in file_managed table.
  $data['file_managed']['extension'] = array(
    'title' => t('Extension'),
    'help' => t('The extension of the file.'),
    'real field' => 'filename',
    'field' => array(
      'id' => 'file_extension',
      'click sortable' => FALSE,
     ),
  );

  // Describes filesize field in file_managed table.
  $data['file_managed']['filesize'] = array(
    'title' => t('Size'),
    'help' => t('The size of the file.'),
    'field' => array(
      'id' => 'file_size',
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
  );

  // Describes status field in file_managed table.
  $data['file_managed']['status'] = array(
    'title' => t('Status'),
    'help' => t('The status of the file.'),
    'field' => array(
      'id' => 'file_status',
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'file_status',
    ),
  );

  // Describes created field in file_managed table.
  $data['file_managed']['created'] = array(
    'title' => t('Upload date'),
    'help' => t('The date the file was uploaded.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  // Describes changed field in file_managed table.
  $data['file_managed']['changed'] = array(
    'title' => t('Modified date'),
    'help' => t('The date the file was last changed.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  // Describes uid field in file_managed table.
  $data['file_managed']['uid'] = array(
    'title' => t('User who uploaded'),
    'help' => t('The user that uploaded the file.'),
    'relationship' => array(
      'title' => t('User who uploaded'),
      'label' => t('User who uploaded'),
      'base' => 'users',
      'base field' => 'uid',
    ),
  );

  // Sets 'group' index for file_usage table.
  $data['file_usage']['table']['group']  = t('File Usage');

  // Provide field-type-things to several base tables; on the core files table
  // ("file_managed") so that we can create relationships from files to
  // entities, and then on each core entity type base table so that we can
  // provide general relationships between entities and files.
  $data['file_usage']['table']['join'] = array(
    // Link ourself to the {node} table
    // so we can provide node->file relationships.
    'node' => array(
      'field' => 'id',
      'left_field' => 'nid',
      'extra' => array(array('field' => 'type', 'value' => 'node')),
    ),
    // Link ourself to the {users} table
    // so we can provide user->file relationships.
    'users' => array(
      'field' => 'id',
      'left_field' => 'uid',
      'extra' => array(array('field' => 'type', 'value' => 'user')),
    ),
    // Link ourself to the {comment} table
    // so we can provide comment->file relationships.
    'comment' => array(
      'field' => 'id',
      'left_field' => 'cid',
      'extra' => array(array('field' => 'type', 'value' => 'comment')),
    ),
    // Link ourself to the {taxonomy_term_data} table
    // so we can provide taxonomy_term->file relationships.
    'taxonomy_term_data' => array(
      'field' => 'id',
      'left_field' => 'tid',
      'extra' => array(array('field' => 'type', 'value' => 'taxonomy_term')),
    ),
    // Link ourself to the {taxonomy_vocabulary} table
    // so we can provide taxonomy_vocabulary->file relationships.
    'taxonomy_vocabulary' => array(
      'field' => 'id',
      'left_field' => 'vid',
      'extra' => array(array('field' => 'type', 'value' => 'taxonomy_vocabulary')),
    ),
  );

  // Provide a relationship between the files table and each entity type,
  // and between each entity type and the files table. Entity->file
  // relationships are type-restricted in the joins declared above, and
  // file->entity relationships are type-restricted in the relationship
  // declarations below.

  // Describes relationships between files and nodes.
  $data['file_usage']['file_to_node'] = array(
    'title' => t('Content'),
    'help' => t('Content that is associated with this file, usually because this file is in a field on the content.'),
    // Only provide this field/relationship/etc.,
    // when the 'file_managed' base table is present.
    'skip base' => array('node', 'node_field_revision', 'users', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
    'real field' => 'id',
    'relationship' => array(
      'title' => t('Content'),
      'label' => t('Content'),
      'base' => 'node',
      'base field' => 'nid',
      'relationship field' => 'id',
      'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'operator' => '=', 'value' => 'node')),
    ),
  );
  $data['file_usage']['node_to_file'] = array(
    'title' => t('File'),
    'help' => t('A file that is associated with this node, usually because it is in a field on the node.'),
    // Only provide this field/relationship/etc.,
    // when the 'node' base table is present.
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

  // Describes relationships between files and users.
  $data['file_usage']['file_to_user'] = array(
    'title' => t('User'),
    'help' => t('A user that is associated with this file, usually because this file is in a field on the user.'),
    // Only provide this field/relationship/etc.,
    // when the 'file_managed' base table is present.
    'skip base' => array('node', 'node_field_revision', 'users', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
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
    // Only provide this field/relationship/etc.,
    // when the 'users' base table is present.
    'skip base' => array('file_managed', 'node', 'node_field_revision', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
    'real field' => 'fid',
    'relationship' => array(
      'title' => t('File'),
      'label' => t('File'),
      'base' => 'file_managed',
      'base field' => 'fid',
      'relationship field' => 'fid',
    ),
  );

  // Describes relationships between files and comments.
  $data['file_usage']['file_to_comment'] = array(
    'title' => t('Comment'),
    'help' => t('A comment that is associated with this file, usually because this file is in a field on the comment.'),
    // Only provide this field/relationship/etc.,
    // when the 'file_managed' base table is present.
    'skip base' => array('node', 'node_field_revision', 'users', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
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
    // Only provide this field/relationship/etc.,
    // when the 'comment' base table is present.
    'skip base' => array('file_managed', 'node', 'node_field_revision', 'users', 'taxonomy_term_data', 'taxonomy_vocabulary'),
    'real field' => 'fid',
    'relationship' => array(
      'title' => t('File'),
      'label' => t('File'),
      'base' => 'file_managed',
      'base field' => 'fid',
      'relationship field' => 'fid',
    ),
  );

  // Describes relationships between files and taxonomy_terms.
  $data['file_usage']['file_to_taxonomy_term'] = array(
    'title' => t('Taxonomy Term'),
    'help' => t('A taxonomy term that is associated with this file, usually because this file is in a field on the taxonomy term.'),
    // Only provide this field/relationship/etc.,
    // when the 'file_managed' base table is present.
    'skip base' => array('node', 'node_field_revision', 'users', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
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
    // Only provide this field/relationship/etc.,
    // when the 'taxonomy_term_data' base table is present.
    'skip base' => array('file_managed', 'node', 'node_field_revision', 'users', 'comment', 'taxonomy_vocabulary'),
    'real field' => 'fid',
    'relationship' => array(
      'title' => t('File'),
      'label' => t('File'),
      'base' => 'file_managed',
      'base field' => 'fid',
      'relationship field' => 'fid',
    ),
  );

  // Describes relationships between files and taxonomy_vocabulary items.
  $data['file_usage']['file_to_taxonomy_vocabulary'] = array(
    'title' => t('Taxonomy Vocabulary'),
    'help' => t('A taxonomy vocabulary that is associated with this file, usually because this file is in a field on the taxonomy vocabulary.'),
    // Only provide this field/relationship/etc.,
    // when the 'file_managed' base table is present.
    'skip base' => array('node', 'node_field_revision', 'users', 'comment', 'taxonomy_term_data', 'taxonomy_vocabulary'),
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
    // Only provide this field/relationship/etc.,
    // when the 'taxonomy_vocabulary' base table is present.
    'skip base' => array('file_managed', 'node', 'node_field_revision', 'users', 'comment', 'taxonomy_term_data'),
    'real field' => 'fid',
    'relationship' => array(
      'title' => t('File'),
      'label' => t('File'),
      'base' => 'file_managed',
      'base field' => 'fid',
      'relationship field' => 'fid',
    ),
  );

  // Provide basic fields from the {file_usage} table to all of the base tables
  // we've declared joins to, because there is no 'skip base' property on these
  // fields.
  $data['file_usage']['module'] = array(
    'title' => t('Module'),
    'help' => t('The module managing this file relationship.'),
    'field' => array(
      'id' => 'standard',
     ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  $data['file_usage']['type'] = array(
    'title' => t('Entity type'),
    'help' => t('The type of entity that is related to the file.'),
    'field' => array(
      'id' => 'standard',
     ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  $data['file_usage']['id'] = array(
    'title' => t('Entity ID'),
    'help' => t('The ID of the entity that is related to the file.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  $data['file_usage']['count'] = array(
    'title' => t('Use count'),
    'help' => t('The number of times the file is used by this entity.'),
    'field' => array(
      'id' => 'numeric',
     ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  $data['file_usage']['entity_label'] = array(
    'title' => t('Entity label'),
    'help' => t('The label of the entity that is related to the file.'),
    'real field' => 'id',
    'field' => array(
      'id' => 'entity_label',
      'entity type field' => 'type',
    ),
  );

  return $data;
}

/**
 * Implements hook_field_views_data().
 *
 * Views integration for file fields. Adds a file relationship to the default
 * field data.
 *
 * @see field_views_field_default_views_data()
 */
function file_field_views_data(FieldConfigInterface $field) {
  $data = field_views_field_default_views_data($field);
  foreach ($data as $table_name => $table_data) {
    // Add the relationship only on the fid field.
    $data[$table_name][$field->getName() . '_target_id']['relationship'] = array(
      'id' => 'standard',
      'base' => 'file_managed',
      'entity type' => 'file',
      'base field' => 'target_id',
      'label' => t('file from !field_name', array('!field_name' => $field->getName())),
    );
  }

  return $data;
}

/**
 * Implements hook_field_views_data_views_data_alter().
 *
 * Views integration to provide reverse relationships on file fields.
 */
function file_field_views_data_views_data_alter(array &$data, FieldConfigInterface $field) {
  $entity_type_id = $field->entity_type;
  $entity_type = \Drupal::entityManager()->getDefinition($entity_type_id);
  $field_name = $field->getName();
  $pseudo_field_name = 'reverse_' . $field_name . '_' . $entity_type_id;

  list($label) = field_views_field_label($entity_type_id, $field_name);

  $data['file_managed'][$pseudo_field_name]['relationship'] = array(
    'title' => t('@entity using @field', array('@entity' => $entity_type->getLabel(), '@field' => $label)),
    'help' => t('Relate each @entity with a @field set to the file.', array('@entity' => $entity_type->getLabel(), '@field' => $label)),
    'id' => 'entity_reverse',
    'field_name' => $field_name,
    'entity_type' => $entity_type_id,
    'field table' => ContentEntityDatabaseStorage::_fieldTableName($field),
    'field field' => $field_name . '_target_id',
    'base' => $entity_type->getBaseTable(),
    'base field' => $entity_type->getKey('id'),
    'label' => t('!field_name', array('!field_name' => $field_name)),
    'join_extra' => array(
      0 => array(
        'field' => 'deleted',
        'value' => 0,
        'numeric' => TRUE,
      ),
    ),
  );
}
