<?php

/**
 * @file
 * Definition of Drupal\file\Plugin\Core\Entity\File.
 */

namespace Drupal\file\Plugin\Core\Entity;

use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Entity\Annotation\EntityType;
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
 *   module = "file",
 *   controllers = {
 *     "storage" = "Drupal\file\FileStorageController",
 *     "render" = "Drupal\Core\Entity\EntityRenderController"
 *   },
 *   base_table = "file_managed",
 *   entity_keys = {
 *     "id" = "fid",
 *     "label" = "filename",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class File extends EntityNG implements FileInterface {

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
    return $this->get('uid')->entity->getBCEntity();
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

}
