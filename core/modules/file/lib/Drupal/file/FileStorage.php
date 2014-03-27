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
      ':changed' => REQUEST_TIME - DRUPAL_MAXIMUM_TEMP_FILE_AGE
    ));
  }
}
