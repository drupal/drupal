<?php

/**
 * @file
 * Contains \Drupal\block_content\BlockContentStorageSchema.
 */

namespace Drupal\block_content;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the block content schema handler.
 */
class BlockContentStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['block_content_field_data']['fields']['info']['not null'] = TRUE;

    $schema['block_content_field_data']['unique keys'] += array(
      'block_content__info' => array('info', 'langcode'),
    );

    return $schema;
  }

}
