<?php

/**
 * @file
 * Contains \Drupal\file\Entity\File.
 */

namespace Drupal\file\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\file\FileInterface;
use Drupal\user\UserInterface;

/**
 * Defines the file entity class.
 *
 * @ContentEntityType(
 *   id = "file",
 *   label = @Translation("File"),
 *   handlers = {
 *     "storage" = "Drupal\file\FileStorage",
 *     "storage_schema" = "Drupal\file\FileStorageSchema",
 *     "access" = "Drupal\file\FileAccessControlHandler",
 *     "views_data" = "Drupal\file\FileViewsData",
 *   },
 *   base_table = "file_managed",
 *   entity_keys = {
 *     "id" = "fid",
 *     "label" = "filename",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class File extends ContentEntityBase implements FileInterface {

  use EntityChangedTrait;

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
  public function url($rel = 'canonical', $options = array()) {
    return file_create_url($this->getFileUri());
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
  public function getCreatedTime() {
    return $this->get('created')->value;
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
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
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
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    // Automatically detect filename if not set.
    if (!isset($values['filename']) && isset($values['uri'])) {
      $values['filename'] = drupal_basename($values['uri']);
    }

    // Automatically detect filemime if not set.
    if (!isset($values['filemime']) && isset($values['uri'])) {
      $values['filemime'] = \Drupal::service('file.mime_type.guesser')->guess($values['uri']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    $this->setSize(filesize($this->getFileUri()));
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    foreach ($entities as $entity) {
      // Delete all remaining references to this file.
      $file_usage = \Drupal::service('file.usage')->listUsage($entity);
      if (!empty($file_usage)) {
        foreach ($file_usage as $module => $usage) {
          \Drupal::service('file.usage')->delete($entity, $module);
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['fid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('File ID'))
      ->setDescription(t('The file ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The file UUID.'))
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The file language code.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the file.'))
      ->setSetting('target_type', 'user');

    $fields['filename'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Filename'))
      ->setDescription(t('Name of the file with no path components.'));

    $fields['uri'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URI'))
      ->setDescription(t('The URI to access the file (either local or remote).'))
      ->setSetting('max_length', 255)
      ->setSetting('case_sensitive', TRUE)
      ->addConstraint('FileUriUnique');

    $fields['filemime'] = BaseFieldDefinition::create('string')
      ->setLabel(t('File MIME type'))
      ->setSetting('is_ascii', TRUE)
      ->setDescription(t("The file's MIME type."));

    $fields['filesize'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('File size'))
      ->setDescription(t('The size of the file in bytes.'))
      ->setSetting('unsigned', TRUE)
      ->setSetting('size', 'big');

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of the file, temporary (FALSE) and permanent (TRUE).'))
      ->setDefaultValue(FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The timestamp that the file was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The timestamp that the file was last changed.'));

    return $fields;
  }

}
