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
