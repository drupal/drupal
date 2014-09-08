<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermStorageSchema.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the term schema handler.
 */
class TermStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset = FALSE);

    if (isset($schema['taxonomy_term_field_data'])) {
      // Marking the respective fields as NOT NULL makes the indexes more
      // performant.
      $schema['taxonomy_term_field_data']['fields']['weight']['not null'] = TRUE;
      $schema['taxonomy_term_field_data']['fields']['name']['not null'] = TRUE;

      unset($schema['taxonomy_term_field_data']['indexes']['taxonomy_term_field__vid__target_id']);
      unset($schema['taxonomy_term_field_data']['indexes']['taxonomy_term_field__description__format']);
      $schema['taxonomy_term_field_data']['indexes'] += array(
        'taxonomy_term__tree' => array('vid', 'weight', 'name'),
        'taxonomy_term__vid_name' => array('vid', 'name'),
        'taxonomy_term__name' => array('name'),
      );
    }

    $schema['taxonomy_term_hierarchy'] = array(
      'description' => 'Stores the hierarchical relationship between terms.',
      'fields' => array(
        'tid' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Primary Key: The {taxonomy_term_data}.tid of the term.',
        ),
        'parent' => array(
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => "Primary Key: The {taxonomy_term_data}.tid of the term's parent. 0 indicates no parent.",
        ),
      ),
      'indexes' => array(
        'parent' => array('parent'),
      ),
      'foreign keys' => array(
        'taxonomy_term_data' => array(
          'table' => 'taxonomy_term_data',
          'columns' => array('tid' => 'tid'),
        ),
      ),
      'primary key' => array('tid', 'parent'),
    );

    $schema['taxonomy_index'] = array(
      'description' => 'Maintains denormalized information about node/term relationships.',
      'fields' => array(
        'nid' => array(
          'description' => 'The {node}.nid this record tracks.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'tid' => array(
          'description' => 'The term ID.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'sticky' => array(
          'description' => 'Boolean indicating whether the node is sticky.',
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
          'size' => 'tiny',
        ),
        'created' => array(
          'description' => 'The Unix timestamp when the node was created.',
          'type' => 'int',
          'not null' => TRUE,
          'default'=> 0,
        ),
      ),
      'primary key' => array('nid', 'tid'),
      'indexes' => array(
        'term_node' => array('tid', 'sticky', 'created'),
      ),
      'foreign keys' => array(
        'tracked_node' => array(
          'table' => 'node',
          'columns' => array('nid' => 'nid'),
        ),
        'term' => array(
          'table' => 'taxonomy_term_data',
          'columns' => array('tid' => 'tid'),
        ),
      ),
    );

    return $schema;
  }

}
