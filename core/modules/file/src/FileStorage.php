<?php

/**
 * @file
 * Definition of Drupal\file\FileStorage.
 */

namespace Drupal\file;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;

/**
 * File storage for files.
 */
class FileStorage extends ContentEntityDatabaseStorage implements FileStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function spaceUsed($uid = NULL, $status = FILE_STATUS_PERMANENT) {
    $query = $this->database->select($this->entityType->getBaseTable(), 'f')
      ->condition('f.status', $status);
    $query->addExpression('SUM(f.filesize)', 'filesize');
    if (isset($uid)) {
      $query->condition('f.uid', $uid);
    }
    return $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    $schema = parent::getSchema();

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
