<?php

namespace Drupal\file;

use Drupal\views\EntityViewsData;

/**
 * Provides views data for the file entity type.
 */
class FileViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // @TODO There is no corresponding information in entity metadata.
    $data['file_managed']['table']['base']['help'] = $this->t('Files maintained by Drupal and various modules.');
    $data['file_managed']['table']['base']['defaults']['field'] = 'filename';
    $data['file_managed']['table']['wizard_id'] = 'file_managed';

    $data['file_managed']['fid']['argument'] = array(
      'id' => 'file_fid',
      // The field to display in the summary.
      'name field' => 'filename',
      'numeric' => TRUE,
    );
    $data['file_managed']['fid']['relationship'] = array(
      'title' => $this->t('File usage'),
      'help' => $this->t('Relate file entities to their usage.'),
      'id' => 'standard',
      'base' => 'file_usage',
      'base field' => 'fid',
      'field' => 'fid',
      'label' => $this->t('File usage'),
    );

    $data['file_managed']['uri']['field']['default_formatter'] = 'file_uri';

    $data['file_managed']['filemime']['field']['default_formatter'] = 'file_filemime';

    $data['file_managed']['extension'] = array(
      'title' => $this->t('Extension'),
      'help' => $this->t('The extension of the file.'),
      'real field' => 'filename',
      'field' => array(
        'entity_type' => 'file',
        'field_name' => 'filename',
        'default_formatter' => 'file_extension',
        'id' => 'field',
        'click sortable' => FALSE,
       ),
    );

    $data['file_managed']['filesize']['field']['default_formatter'] = 'file_size';

    $data['file_managed']['status']['field']['default_formatter_settings'] = [
      'format' => 'custom',
      'format_custom_false' => $this->t('Temporary'),
      'format_custom_true' => $this->t('Permanent'),
    ];
    $data['file_managed']['status']['filter']['id'] = 'file_status';

    $data['file_managed']['uid']['relationship']['title'] = $this->t('User who uploaded');
    $data['file_managed']['uid']['relationship']['label'] = $this->t('User who uploaded');

    $data['file_usage']['table']['group']  = $this->t('File Usage');

    // Provide field-type-things to several base tables; on the core files table
    // ("file_managed") so that we can create relationships from files to
    // entities, and then on each core entity type base table so that we can
    // provide general relationships between entities and files.
    $data['file_usage']['table']['join'] = array(
      'file_managed' => array(
        'field' => 'fid',
        'left_field' => 'fid',
      ),
      // Link ourselves to the {node_field_data} table
      // so we can provide node->file relationships.
      'node_field_data' => array(
        'field' => 'id',
        'left_field' => 'nid',
        'extra' => array(array('field' => 'type', 'value' => 'node')),
      ),
      // Link ourselves to the {users_field_data} table
      // so we can provide user->file relationships.
      'users_field_data' => array(
        'field' => 'id',
        'left_field' => 'uid',
        'extra' => array(array('field' => 'type', 'value' => 'user')),
      ),
      // Link ourselves to the {comment_field_data} table
      // so we can provide comment->file relationships.
      'comment' => array(
        'field' => 'id',
        'left_field' => 'cid',
        'extra' => array(array('field' => 'type', 'value' => 'comment')),
      ),
      // Link ourselves to the {taxonomy_term_field_data} table
      // so we can provide taxonomy_term->file relationships.
      'taxonomy_term_data' => array(
        'field' => 'id',
        'left_field' => 'tid',
        'extra' => array(array('field' => 'type', 'value' => 'taxonomy_term')),
      ),
    );

    // Provide a relationship between the files table and each entity type,
    // and between each entity type and the files table. Entity->file
    // relationships are type-restricted in the joins declared above, and
    // file->entity relationships are type-restricted in the relationship
    // declarations below.

    // Describes relationships between files and nodes.
    $data['file_usage']['file_to_node'] = array(
      'title' => $this->t('Content'),
      'help' => $this->t('Content that is associated with this file, usually because this file is in a field on the content.'),
      // Only provide this field/relationship/etc.,
      // when the 'file_managed' base table is present.
      'skip base' => array('node_field_data', 'node_field_revision', 'users_field_data', 'comment_field_data', 'taxonomy_term_field_data'),
      'real field' => 'id',
      'relationship' => array(
        'title' => $this->t('Content'),
        'label' => $this->t('Content'),
        'base' => 'node_field_data',
        'base field' => 'nid',
        'relationship field' => 'id',
        'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'operator' => '=', 'value' => 'node')),
      ),
    );
    $data['file_usage']['node_to_file'] = array(
      'title' => $this->t('File'),
      'help' => $this->t('A file that is associated with this node, usually because it is in a field on the node.'),
      // Only provide this field/relationship/etc.,
      // when the 'node' base table is present.
      'skip base' => array('file_managed', 'users_field_data', 'comment_field_data', 'taxonomy_term_field_data'),
      'real field' => 'fid',
      'relationship' => array(
        'title' => $this->t('File'),
        'label' => $this->t('File'),
        'base' => 'file_managed',
        'base field' => 'fid',
        'relationship field' => 'fid',
      ),
    );

    // Describes relationships between files and users.
    $data['file_usage']['file_to_user'] = array(
      'title' => $this->t('User'),
      'help' => $this->t('A user that is associated with this file, usually because this file is in a field on the user.'),
      // Only provide this field/relationship/etc.,
      // when the 'file_managed' base table is present.
      'skip base' => array('node_field_data', 'node_field_revision', 'users_field_data', 'comment_field_data', 'taxonomy_term_field_data'),
      'real field' => 'id',
      'relationship' => array(
        'title' => $this->t('User'),
        'label' => $this->t('User'),
        'base' => 'users',
        'base field' => 'uid',
        'relationship field' => 'id',
        'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'operator' => '=', 'value' => 'user')),
      ),
    );
    $data['file_usage']['user_to_file'] = array(
      'title' => $this->t('File'),
      'help' => $this->t('A file that is associated with this user, usually because it is in a field on the user.'),
      // Only provide this field/relationship/etc.,
      // when the 'users' base table is present.
      'skip base' => array('file_managed', 'node_field_data', 'node_field_revision', 'comment_field_data', 'taxonomy_term_field_data'),
      'real field' => 'fid',
      'relationship' => array(
        'title' => $this->t('File'),
        'label' => $this->t('File'),
        'base' => 'file_managed',
        'base field' => 'fid',
        'relationship field' => 'fid',
      ),
    );

    // Describes relationships between files and comments.
    $data['file_usage']['file_to_comment'] = array(
      'title' => $this->t('Comment'),
      'help' => $this->t('A comment that is associated with this file, usually because this file is in a field on the comment.'),
      // Only provide this field/relationship/etc.,
      // when the 'file_managed' base table is present.
      'skip base' => array('node_field_data', 'node_field_revision', 'users_field_data', 'comment_field_data', 'taxonomy_term_field_data'),
      'real field' => 'id',
      'relationship' => array(
        'title' => $this->t('Comment'),
        'label' => $this->t('Comment'),
        'base' => 'comment_field_data',
        'base field' => 'cid',
        'relationship field' => 'id',
        'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'operator' => '=', 'value' => 'comment')),
      ),
    );
    $data['file_usage']['comment_to_file'] = array(
      'title' => $this->t('File'),
      'help' => $this->t('A file that is associated with this comment, usually because it is in a field on the comment.'),
      // Only provide this field/relationship/etc.,
      // when the 'comment' base table is present.
      'skip base' => array('file_managed', 'node_field_data', 'node_field_revision', 'users_field_data', 'taxonomy_term_field_data'),
      'real field' => 'fid',
      'relationship' => array(
        'title' => $this->t('File'),
        'label' => $this->t('File'),
        'base' => 'file_managed',
        'base field' => 'fid',
        'relationship field' => 'fid',
      ),
    );

    // Describes relationships between files and taxonomy_terms.
    $data['file_usage']['file_to_taxonomy_term'] = array(
      'title' => $this->t('Taxonomy Term'),
      'help' => $this->t('A taxonomy term that is associated with this file, usually because this file is in a field on the taxonomy term.'),
      // Only provide this field/relationship/etc.,
      // when the 'file_managed' base table is present.
      'skip base' => array('node_field_data', 'node_field_revision', 'users_field_data', 'comment_field_data', 'taxonomy_term_field_data'),
      'real field' => 'id',
      'relationship' => array(
        'title' => $this->t('Taxonomy Term'),
        'label' => $this->t('Taxonomy Term'),
        'base' => 'taxonomy_term_data',
        'base field' => 'tid',
        'relationship field' => 'id',
        'extra' => array(array('table' => 'file_usage', 'field' => 'type', 'operator' => '=', 'value' => 'taxonomy_term')),
      ),
    );
    $data['file_usage']['taxonomy_term_to_file'] = array(
      'title' => $this->t('File'),
      'help' => $this->t('A file that is associated with this taxonomy term, usually because it is in a field on the taxonomy term.'),
      // Only provide this field/relationship/etc.,
      // when the 'taxonomy_term_data' base table is present.
      'skip base' => array('file_managed', 'node_field_data', 'node_field_revision', 'users_field_data', 'comment_field_data'),
      'real field' => 'fid',
      'relationship' => array(
        'title' => $this->t('File'),
        'label' => $this->t('File'),
        'base' => 'file_managed',
        'base field' => 'fid',
        'relationship field' => 'fid',
      ),
    );

    // Provide basic fields from the {file_usage} table to all of the base tables
    // we've declared joins to, because there is no 'skip base' property on these
    // fields.
    $data['file_usage']['module'] = array(
      'title' => $this->t('Module'),
      'help' => $this->t('The module managing this file relationship.'),
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
      'title' => $this->t('Entity type'),
      'help' => $this->t('The type of entity that is related to the file.'),
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
      'title' => $this->t('Entity ID'),
      'help' => $this->t('The ID of the entity that is related to the file.'),
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
      'title' => $this->t('Use count'),
      'help' => $this->t('The number of times the file is used by this entity.'),
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
      'title' => $this->t('Entity label'),
      'help' => $this->t('The label of the entity that is related to the file.'),
      'real field' => 'id',
      'field' => array(
        'id' => 'entity_label',
        'entity type field' => 'type',
      ),
    );

    return $data;
  }

}
