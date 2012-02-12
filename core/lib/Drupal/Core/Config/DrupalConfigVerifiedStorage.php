<?php

namespace Drupal\Core\Config;

use Drupal\Core\Config\DrupalConfigVerifiedStorageInterface;
use Drupal\Core\Config\SignedFileStorage;

/**
 * @todo
 */
abstract class DrupalConfigVerifiedStorage implements DrupalConfigVerifiedStorageInterface {

  /**
   * Implements DrupalConfigVerifiedStorageInterface::__construct().
   */
  function __construct($name) {
    $this->name = $name;
  }

  /**
   * @todo
   *
   * @return
   *   @todo
   */
  protected function signedFileStorage() {
    return new SignedFileStorage($this->name);
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::copyToFile().
   */
  public function copyToFile() {
    return $this->signedFileStorage()->write($this->read());
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
    $this->copyToFile();
  }

  /**
   * Implements DrupalConfigVerifiedStorageInterface::delete().
   */
  public function delete() {
    $this->deleteFromActive();
    $this->deleteFile();
  }
}
