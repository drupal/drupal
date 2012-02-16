<?php

namespace Drupal\Core\Config;

/**
 * Represents the signed file storage interface.
 *
 * Classes implementing this interface allow reading and writing configuration
 * data to and from disk, while automatically managing and verifying
 * cryptographic signatures.
 */
class SignedFileStorage {

  /**
   * Constructs a SignedFileStorage object.
   *
   * @param string $name
   *   The name for the configuration data. Should be lowercase.
   */
  public function __construct($name) {
    $this->name = $name;
  }

  /**
   * Reads and returns a signed file and its signature.
   *
   * @return
   *   An array with "signature" and "data" keys.
   *
   * @throws
   *   Exception
   */
  protected function readWithSignature() {
    $content = file_get_contents($this->getFilePath());
    if ($content === FALSE) {
      throw new \Exception('Read file is invalid.');
    }
    $signature = file_get_contents($this->getFilePath() . '.sig');
    if ($signature === FALSE) {
      throw new \Exception('Signature file is invalid.');
    }
    return array('data' => $content, 'signature' => $signature);
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
   * Recreates the signature for the file.
   */
  public function resign() {
    if ($this->exists()) {
      $parts = $this->readWithSignature();
      $this->write($parts['data']);
    }
  }

  /**
   * Cryptographically verifies the integrity of the configuration file.
   *
   * @param $contentOnSuccess
   *   Whether or not to return the contents of the verified configuration file.
   *
   * @return mixed
   *   If $contentOnSuccess was TRUE, returns the contents of the verified
   *   configuration file; otherwise returns TRUE on success. Always returns
   *   FALSE if the configuration file was not successfully verified.
   */
  public function verify($contentOnSuccess = FALSE) {
    if ($this->exists()) {
      $split = $this->readWithSignature();
      $expected_signature = config_sign_data($split['data']);
      if ($expected_signature === $split['signature']) {
        if ($contentOnSuccess) {
          return $split['data'];
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Writes the contents of the configuration file to disk.
   *
   * @param $data
   *   The data to be written to the file.
   *
   * @throws
   *   Exception
   *
   * @todo What format is $data in?
   */
  public function write($data) {
    $signature = config_sign_data($data);
    if (!file_put_contents($this->getFilePath(), $data)) {
      throw new \Exception('Failed to write configuration file: ' . $this->getFilePath());
    }
    if (!file_put_contents($this->getFilePath() . '.sig', $signature)) {
      throw new \Exception('Failed to write signature file: ' . $this->getFilePath());
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
      $verification = $this->verify(TRUE);
      if ($verification === FALSE) {
        throw new \Exception('Invalid signature in file header.');
      }
      return $verification;
    }
  }

  /**
   * Deletes a configuration file.
   */
  public function delete() {
    // Needs error handling and etc.
    @drupal_unlink($this->getFilePath());
    @drupal_unlink($this->getFilePath() . '.sig');
  }
}
