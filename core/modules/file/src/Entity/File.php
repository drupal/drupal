<?php

namespace Drupal\file\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\File\Exception\FileException;
use Drupal\file\FileInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the file entity class.
 *
 * @ingroup file
 *
 * @ContentEntityType(
 *   id = "file",
 *   label = @Translation("File"),
 *   label_collection = @Translation("Files"),
 *   label_singular = @Translation("file"),
 *   label_plural = @Translation("files"),
 *   label_count = @PluralTranslation(
 *     singular = "@count file",
 *     plural = "@count files",
 *   ),
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
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   }
 * )
 */
class File extends ContentEntityBase implements FileInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

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
  public function createFileUrl($relative = TRUE) {
    $url = file_create_url($this->getFileUri());
    if ($relative && $url) {
      $url = file_url_transform_relative($url);
    }
    return $url;
  }

  /**
   * {@inheritdoc}
   *
   * @see file_url_transform_relative()
   */
  public function url($rel = 'canonical', $options = []) {
    @trigger_error('File entities returning the URL to the physical file in File::url() is deprecated, use $file->createFileUrl() instead. See https://www.drupal.org/node/3019830', E_USER_DEPRECATED);
    return $this->createFileUrl(FALSE);
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
      $values['filename'] = \Drupal::service('file_system')->basename($values['uri']);
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

    // The file itself might not exist or be available right now.
    $uri = $this->getFileUri();
    $size = @filesize($uri);

    // Set size unless there was an error.
    if ($size !== FALSE) {
      $this->setSize($size);
    }
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
      try {
        \Drupal::service('file_system')->delete($entity->getFileUri());
      }
      catch (FileException $e) {
        // Ignore and continue.
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['fid']->setLabel(t('File ID'))
      ->setDescription(t('The file ID.'));

    $fields['uuid']->setDescription(t('The file UUID.'));

    $fields['langcode']->setLabel(t('Language code'))
      ->setDescription(t('The file language code.'));

    $fields['uid']
      ->setDescription(t('The user ID of the file.'));

    $fields['filename'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Filename'))
      ->setDescription(t('Name of the file with no path components.'));

    $fields['uri'] = BaseFieldDefinition::create('file_uri')
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

  /**
   * {@inheritdoc}
   */
  public static function getDefaultEntityOwner() {
    return NULL;
  }

}
