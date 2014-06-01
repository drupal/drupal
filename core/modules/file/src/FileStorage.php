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
  public function retrieveTemporaryFiles() {
    // Use separate placeholders for the status to avoid a bug in some versions
    // of PHP. See http://drupal.org/node/352956.
    return $this->database->query('SELECT fid FROM {' . $this->entityType->getBaseTable() . '} WHERE status <> :permanent AND changed < :changed', array(
      ':permanent' => FILE_STATUS_PERMANENT,
      ':changed' => REQUEST_TIME - \Drupal::config('system.file')->get('temporary_maximum_age'),
    ))->fetchCol();
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
      // FIXME We have an index size of 255, but the max URI length is 2048 so
      // this might now always work. Should we replace this with a regular
      // index?
      'file__uri' => array(array('uri', 255)),
    );

    return $schema;
  }

}
