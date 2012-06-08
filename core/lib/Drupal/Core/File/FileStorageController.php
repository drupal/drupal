<?php

/**
 * @file
 * Definition of Drupal\Core\File\FileStorageController.
 */

namespace Drupal\Core\File;

use Drupal\entity\DatabaseStorageController;
use Drupal\entity\EntityInterface;

/**
 * File storage controller for files.
 */
class FileStorageController extends DatabaseStorageController {

  /**
   * Overrides Drupal\entity\DatabaseStorageController::create().
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
   * Overrides Drupal\entity\DatabaseStorageController::presave().
   */
  protected function preSave(EntityInterface $entity) {
    $entity->timestamp = REQUEST_TIME;
    $entity->filesize = filesize($entity->uri);
    if (!isset($entity->langcode)) {
      // Default the file's language code to none, because files are language
      // neutral more often than language dependent. Until we have better
      // flexible settings.
      // @todo See http://drupal.org/node/258785 and followups.
      $entity->langcode = LANGUAGE_NOT_SPECIFIED;
    }
  }

  /**
   * Overrides Drupal\entity\DatabaseStorageController::preDelete().
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

}
