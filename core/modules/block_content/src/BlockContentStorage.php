<?php

/**
 * @file
 * Contains \Drupal\block_content\BlockContentStorage.
 */

namespace Drupal\block_content;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;

/**
 * Provides storage for the 'block_content' entity type.
 */
class BlockContentStorage extends ContentEntityDatabaseStorage {

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    $schema = parent::getSchema();

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['block_content_field_data']['fields']['info']['not null'] = TRUE;

    $schema['block_content_field_data']['unique keys'] += array(
      'block_content__info' => array('info', 'langcode'),
    );

    return $schema;
  }

}
