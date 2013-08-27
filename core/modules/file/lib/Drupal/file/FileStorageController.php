<?php

/**
 * @file
 * Definition of Drupal\file\FileStorageController.
 */

namespace Drupal\file;

use Drupal\Core\Entity\DatabaseStorageControllerNG;

/**
 * File storage controller for files.
 */
class FileStorageController extends DatabaseStorageControllerNG implements FileStorageControllerInterface {

  /**
   * {@inheritdoc}
   */
  public function spaceUsed($uid = NULL, $status = FILE_STATUS_PERMANENT) {
    $query = $this->database->select($this->entityInfo['base_table'], 'f')
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
    return $this->database->query('SELECT fid FROM {' . $this->entityInfo['base_table'] . '} WHERE status <> :permanent AND timestamp < :timestamp', array(
      ':permanent' => FILE_STATUS_PERMANENT,
      ':timestamp' => REQUEST_TIME - DRUPAL_MAXIMUM_TEMP_FILE_AGE
    ));
  }
}
