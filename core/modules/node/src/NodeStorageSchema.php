<?php

/**
 * @file
 * Contains \Drupal\node\NodeStorageSchema.
 */

namespace Drupal\node;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the node schema handler.
 */
class NodeStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['node_field_data']['fields']['changed']['not null'] = TRUE;
    $schema['node_field_data']['fields']['created']['not null'] = TRUE;
    $schema['node_field_data']['fields']['default_langcode']['not null'] = TRUE;
    $schema['node_field_data']['fields']['promote']['not null'] = TRUE;
    $schema['node_field_data']['fields']['status']['not null'] = TRUE;
    $schema['node_field_data']['fields']['sticky']['not null'] = TRUE;
    $schema['node_field_data']['fields']['title']['not null'] = TRUE;
    $schema['node_field_revision']['fields']['default_langcode']['not null'] = TRUE;

    // @todo Revisit index definitions in https://drupal.org/node/2015277.
    $schema['node_revision']['indexes'] += array(
      'node__langcode' => array('langcode'),
    );
    $schema['node_revision']['foreign keys'] += array(
      'node__revision_author' => array(
        'table' => 'users',
        'columns' => array('revision_uid' => 'uid'),
      ),
    );

    $schema['node_field_data']['indexes'] += array(
      'node__changed' => array('changed'),
      'node__created' => array('created'),
      'node__default_langcode' => array('default_langcode'),
      'node__langcode' => array('langcode'),
      'node__frontpage' => array('promote', 'status', 'sticky', 'created'),
      'node__status_type' => array('status', 'type', 'nid'),
      'node__title_type' => array('title', array('type', 4)),
    );

    $schema['node_field_revision']['indexes'] += array(
      'node__default_langcode' => array('default_langcode'),
      'node__langcode' => array('langcode'),
    );

    return $schema;
  }

}
