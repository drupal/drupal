<?php

/**
 * @file
 * Contains \Drupal\file\FileStorageSchema.
 */

namespace Drupal\file;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the file schema handler.
 */
class FileStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['file_managed']['fields']['status']['not null'] = TRUE;
    $schema['file_managed']['fields']['changed']['not null'] = TRUE;
    $schema['file_managed']['fields']['uri']['not null'] = TRUE;

    // @todo There should be a 'binary' field type or setting.
    $schema['file_managed']['fields']['uri']['binary'] = TRUE;
    $schema['file_managed']['indexes'] += array(
      'file__status' => array('status'),
      'file__changed' => array('changed'),
    );
    $schema['file_managed']['unique keys'] += array(
      'file__uri' => array('uri'),
    );

    return $schema;
  }

}
