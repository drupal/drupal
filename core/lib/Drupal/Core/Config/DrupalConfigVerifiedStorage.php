<?php

namespace Drupal\Core\Config;

use Drupal\Core\Config\DrupalConfigVerifiedStorageInterface;
use Drupal\Core\Config\SignedFileStorage;

/**
 * @todo
 */
abstract class DrupalConfigVerifiedStorage implements DrupalConfigVerifiedStorageInterface {

  /**
   * The local signed file object to read from and write to.
   *
   * @var SignedFileStorage
   */
  protected $signedFile;

  /**
   * Implements DrupalConfigVerifiedStorageInterface::__construct().
   */
  function __construct($name) {
    $this->name = $name;
  }

  /**
   * Instantiates a new signed file object or returns the existing one.
   *
   * @return SignedFileStorage
   *   The signed file object for this configuration object.
   */
  protected function signedFileStorage() {
    if (!isset($this->signedFile)) {
      $this->signedFile = new SignedFileStorage($this->name);
    }
    return $this->signedFile;
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
    return $this->signedFileStorage()->delete();
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
    return $this->signedFileStorage()->read($this->name);
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
    return $this->signedFileStorage()->write($data);
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::delete().
   */
  public function delete() {
    $this->deleteFromActive();
    $this->deleteFile();
  }
}
