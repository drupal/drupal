<?php

/**
 * @file
 * Definition of Drupal\file\FileStorageController.
 */

namespace Drupal\file;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;

/**
 * File storage controller for files.
 */
class FileStorageController extends DatabaseStorageControllerNG {

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
    $entity->setSize(filesize($entity->getFileUri()));
    if (!$entity->langcode->value) {
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
      file_unmanaged_delete($entity->getFileUri());
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

  /**
   * {@inheritdoc}
   */
  public function baseFieldDefinitions() {
    $properties['fid'] = array(
      'label' => t('File ID'),
      'description' => t('The file ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The file UUID.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The file language code.'),
      'type' => 'language_field',
    );
    $properties['uid'] = array(
      'label' => t('User ID'),
      'description' => t('The user ID of the file.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'user'),
    );
    $properties['filename'] = array(
      'label' => t('Filename'),
      'description' => t('Name of the file with no path components.'),
      'type' => 'string_field',
    );
    $properties['uri'] = array(
      'label' => t('URI'),
      'description' => t('The URI to access the file (either local or remote).'),
      'type' => 'string_field',
    );
    $properties['filemime'] = array(
      'label' => t('File MIME type'),
      'description' => t("The file's MIME type."),
      'type' => 'string_field',
    );
    $properties['filesize'] = array(
      'label' => t('File size'),
      'description' => t('The size of the file in bytes.'),
      'type' => 'boolean_field',
    );
    $properties['status'] = array(
      'label' => t('Status'),
      'description' => t('The status of the file, temporary (0) and permanent (1)'),
      'type' => 'integer_field',
    );
    $properties['timestamp'] = array(
      'label' => t('Created'),
      'description' => t('The time that the node was created.'),
      'type' => 'integer_field',
    );
    return $properties;
  }
}
