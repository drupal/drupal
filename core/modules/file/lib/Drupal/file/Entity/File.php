<?php

/**
 * @file
 * Definition of Drupal\file\Entity\File.
 */

namespace Drupal\file\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Language\Language;
use Drupal\file\FileInterface;
use Drupal\user\UserInterface;

/**
 * Defines the file entity class.
 *
 * @EntityType(
 *   id = "file",
 *   label = @Translation("File"),
 *   controllers = {
 *     "storage" = "Drupal\file\FileStorageController",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder"
 *   },
 *   base_table = "file_managed",
 *   entity_keys = {
 *     "id" = "fid",
 *     "label" = "filename",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class File extends ContentEntityBase implements FileInterface {

  /**
   * The plain data values of the contained properties.
   *
   * Define default values.
   *
   * @var array
   */
  protected $values = array(
    'langcode' => array(Language::LANGCODE_DEFAULT => array(0 => array('value' => Language::LANGCODE_NOT_SPECIFIED))),
  );

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('fid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilename() {
    return $this->get('filename')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFilename($filename) {
    $this->get('filename')->value = $filename;
  }

  /**
   * {@inheritdoc}
   */
  public function getFileUri() {
    return $this->get('uri')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFileUri($uri) {
    $this->get('uri')->value = $uri;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    return $this->get('filemime')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMimeType($mime) {
    $this->get('filemime')->value = $mime;
  }

  /**
   * {@inheritdoc}
   */
  public function getSize() {
    return $this->get('filesize')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSize($size) {
    $this->get('filesize')->value = $size;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $user) {
    return $this->get('uid')->entity = $user;
  }

  /**
   * {@inheritdoc}
   */
  public function isPermanent() {
    return $this->get('status')->value == FILE_STATUS_PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function isTemporary() {
    return $this->get('status')->value == 0;
  }

  /**
   * {@inheritdoc}
   */
  public function setPermanent() {
    $this->get('status')->value = FILE_STATUS_PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function setTemporary() {
    $this->get('status')->value = 0;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageControllerInterface $storage_controller, array &$values) {
    // Automatically detect filename if not set.
    if (!isset($values['filename']) && isset($values['uri'])) {
      $values['filename'] = drupal_basename($values['uri']);
    }

    // Automatically detect filemime if not set.
    if (!isset($values['filemime']) && isset($values['uri'])) {
      $values['filemime'] = file_get_mimetype($values['uri']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageControllerInterface $storage_controller) {
    parent::preSave($storage_controller);

    $this->timestamp = REQUEST_TIME;
    $this->setSize(filesize($this->getFileUri()));
    if (!isset($this->langcode->value)) {
      // Default the file's language code to none, because files are language
      // neutral more often than language dependent. Until we have better
      // flexible settings.
      // @todo See http://drupal.org/node/258785 and followups.
      $this->langcode = Language::LANGCODE_NOT_SPECIFIED;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageControllerInterface $storage_controller, array $entities) {
    parent::preDelete($storage_controller, $entities);

    foreach ($entities as $entity) {
      // Delete all remaining references to this file.
      $file_usage = file_usage()->listUsage($entity);
      if (!empty($file_usage)) {
        foreach ($file_usage as $module => $usage) {
          file_usage()->delete($entity, $module);
        }
      }
      // Delete the actual file. Failures due to invalid files and files that
      // were already deleted are logged to watchdog but ignored, the
      // corresponding file entity will be deleted.
      file_unmanaged_delete($entity->getFileUri());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $properties['fid'] = array(
      'label' => t('File ID'),
      'description' => t('The file ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The file UUID.'),
      'type' => 'uuid_field',
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
