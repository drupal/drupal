<?php

namespace Drupal\Core\Config;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\FileStorage;

/**
 * Base class for configuration storage controllers.
 */
abstract class StorageBase implements StorageInterface {

  /**
   * The name of the configuration object.
   *
   * @var string
   */
  protected $name;

  /**
   * The local file object to read from and write to.
   *
   * @var Drupal\Core\Config\FileStorage
   */
  protected $fileStorage;

  /**
   * Implements StorageInterface::__construct().
   */
  function __construct($name = NULL) {
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
   * Implements StorageInterface::copyToFile().
   */
  public function copyToFile() {
    return $this->writeToFile($this->read());
  }

  /**
   * Implements StorageInterface::deleteFile().
   */
  public function deleteFile() {
    return $this->fileStorage()->delete();
  }

  /**
   * Implements StorageInterface::copyFromFile().
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
   * Implements StorageInterface::isOutOfSync().
   */
  public function isOutOfSync() {
    return $this->read() !== $this->readFromFile();
  }

  /**
   * Implements StorageInterface::write().
   */
  public function write($data) {
    $this->writeToActive($data);
    $this->writeToFile($data);
  }

  /**
   * Implements StorageInterface::writeToFile().
   */
  public function writeToFile($data) {
    return $this->fileStorage()->write($data);
  }

  /**
   * Implements StorageInterface::delete().
   */
  public function delete() {
    $this->deleteFromActive();
    $this->deleteFile();
  }

  /**
   * Implements StorageInterface::getName().
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Implements StorageInterface::setName().
   */
  public function setName($name) {
    $this->name = $name;
  }
}
