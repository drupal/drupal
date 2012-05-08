<?php

namespace Drupal\Core\Config;

use Drupal\Core\Config\DrupalConfigVerifiedStorageInterface;
use Drupal\Core\Config\FileStorage;

/**
 * @todo
 */
abstract class DrupalConfigVerifiedStorage implements DrupalConfigVerifiedStorageInterface {

  protected $name;

  /**
   * The local file object to read from and write to.
   *
   * @var Drupal\Core\Config\FileStorage
   */
  protected $fileStorage;

  /**
   * Implements DrupalConfigVerifiedStorageInterface::__construct().
   */
  function __construct($name) {
    $this->name = $name;
  }

  /**
   * Instantiates a new file storage object or returns the existing one.
   *
   * @return Drupal\Core\Config\FileStorage
   *   The file object for this configuration object.
   */
  protected function fileStorage() {
    if (!isset($this->fileStorage)) {
      $this->fileStorage = new FileStorage($this->name);
    }
    return $this->fileStorage;
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::copyToFile().
   */
  public function copyToFile() {
    return $this->writeToFile($this->read());
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::deleteFile().
   */
  public function deleteFile() {
    return $this->fileStorage()->delete();
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::copyFromFile().
   */
  public function copyFromFile() {
    return $this->writeToActive($this->readFromFile());
  }

  /**
   * @todo
   *
   * @return
   *   @todo
   */
  public function readFromFile() {
    return $this->fileStorage()->read($this->name);
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::isOutOfSync().
   */
  public function isOutOfSync() {
    return $this->read() !== $this->readFromFile();
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::write().
   */
  public function write($data) {
    $this->writeToActive($data);
    $this->writeToFile($data);
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::writeToFile().
   */
  public function writeToFile($data) {
    return $this->fileStorage()->write($data);
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::delete().
   */
  public function delete() {
    $this->deleteFromActive();
    $this->deleteFile();
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::getName().
   */
  public function getName() {
    return $this->name;
  }
}
