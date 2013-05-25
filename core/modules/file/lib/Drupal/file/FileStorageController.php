<?php

/**
 * @file
 * Definition of Drupal\file\FileStorageController.
 */

namespace Drupal\file;

use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;

/**
 * File storage controller for files.
 */
class FileStorageController extends DatabaseStorageController {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::create().
   */
  public function create(array $values) {
    // Automatically detect filename if not set.
    if (!isset($values['filename']) && isset($values['uri'])) {
      $values['filename'] = drupal_basename($values['uri']);
    }

    // Automatically detect filemime if not set.
    if (!isset($values['filemime']) && isset($values['uri'])) {
      $values['filemime'] = file_get_mimetype($values['uri']);
    }
    return parent::create($values);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::presave().
   */
  protected function preSave(EntityInterface $entity) {
    $entity->timestamp = REQUEST_TIME;
    $entity->filesize = filesize($entity->uri);
    if (!isset($entity->langcode)) {
      // Default the file's language code to none, because files are language
      // neutral more often than language dependent. Until we have better
      // flexible settings.
      // @todo See http://drupal.org/node/258785 and followups.
      $entity->langcode = Language::LANGCODE_NOT_SPECIFIED;
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preDelete().
   */
  public function preDelete($entities) {
    foreach ($entities as $entity) {
      // Delete the actual file. Failures due to invalid files and files that
      // were already deleted are logged to watchdog but ignored, the
      // corresponding file entity will be deleted.
      file_unmanaged_delete($entity->uri);
    }
    // Delete corresponding file usage entries.
    db_delete('file_usage')
      ->condition('fid', array_keys($entities), 'IN')
      ->execute();
  }

  /**
   * Determines total disk space used by a single user or the whole filesystem.
   *
   * @param int $uid
   *   Optional. A user id, specifying NULL returns the total space used by all
   *   non-temporary files.
   * @param $status
   *   Optional. The file status to consider. The default is to only
   *   consider files in status FILE_STATUS_PERMANENT.
   *
   * @return int
   *   An integer containing the number of bytes used.
   */
  public function spaceUsed($uid = NULL, $status = FILE_STATUS_PERMANENT) {
    $query = db_select($this->entityInfo['base_table'], 'f')
      ->condition('f.status', $status);
    $query->addExpression('SUM(f.filesize)', 'filesize');
    if (isset($uid)) {
      $query->condition('f.uid', $uid);
    }
    return $query->execute()->fetchField();
  }

  /**
   * Retrieve temporary files that are older than DRUPAL_MAXIMUM_TEMP_FILE_AGE.
   *
   *  @return
   *    A list of files to be deleted.
   */
  public function retrieveTemporaryFiles() {
    // Use separate placeholders for the status to avoid a bug in some versions
    // of PHP. See http://drupal.org/node/352956.
    return db_query('SELECT fid FROM {' . $this->entityInfo['base_table'] . '} WHERE status <> :permanent AND timestamp < :timestamp', array(
      ':permanent' => FILE_STATUS_PERMANENT,
      ':timestamp' => REQUEST_TIME - DRUPAL_MAXIMUM_TEMP_FILE_AGE
    ));
  }
}
