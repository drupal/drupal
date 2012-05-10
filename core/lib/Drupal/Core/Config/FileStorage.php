<?php

namespace Drupal\Core\Config;

/**
 * Represents the file storage interface.
 *
 * Classes implementing this interface allow reading and writing configuration
 * data to and from disk.
 */
class FileStorage {

  /**
   * Constructs a FileStorage object.
   *
   * @param string $name
   *   The name for the configuration data. Should be lowercase.
   */
  public function __construct($name) {
    $this->name = $name;
  }

  /**
   * Reads and returns a file.
   *
   * @return
   *   The data of the file.
   *
   * @throws
   *   Exception
   */
  protected function readData() {
    $data = file_get_contents($this->getFilePath());
    if ($data === FALSE) {
      throw new \Exception('Read file is invalid.');
    }
    return $data;
  }

  /**
   * Checks whether the XML configuration file already exists on disk.
   *
   * @return
   *   @todo
   */
  protected function exists() {
    return file_exists($this->getFilePath());
  }

  /**
   * Returns the path to the XML configuration file.
   *
   * @return
   *   @todo
   */
  public function getFilePath() {
    return config_get_config_directory() . '/' . $this->name  . '.xml';
  }

  /**
   * Writes the contents of the configuration file to disk.
   *
   * @param $data
   *   The data to be written to the file.
   *
   * @throws
   *   Exception
   */
  public function write($data) {
    if (!file_put_contents($this->getFilePath(), $data)) {
      throw new \Exception('Failed to write configuration file: ' . $this->getFilePath());
    }
  }

  /**
   * Returns the contents of the configuration file.
   *
   * @return
   *   @todo
   */
  public function read() {
    if ($this->exists()) {
      $data = $this->readData();
      return $data;
    }
    return FALSE;
  }

  /**
   * Deletes a configuration file.
   */
  public function delete() {
    // Needs error handling and etc.
    @drupal_unlink($this->getFilePath());
  }
}
