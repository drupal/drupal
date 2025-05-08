<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\FileTransfer;

use Drupal\Core\FileTransfer\FileTransfer;

/**
 * Mock FileTransfer object for test case.
 */
class TestFileTransfer extends FileTransfer {

  /**
   * {@inheritdoc}
   */
  protected $host = '';

  /**
   * {@inheritdoc}
   */
  protected $username = '';

  /**
   * {@inheritdoc}
   */
  protected $password = '';

  /**
   * {@inheritdoc}
   */
  protected $port = 0;

  /**
   * This is for testing the CopyRecursive logic.
   *
   * @var bool
   */
  public $shouldIsDirectoryReturnTrue = FALSE;

  /**
   * Factory method to create a TestFileTransfer instance.
   */
  public static function factory($jail, $settings) {
    assert(is_array($settings));
    return new TestFileTransfer($jail);
  }

  /**
   * Establishes a mock connection for file transfer.
   */
  public function connect() {
    // @phpstan-ignore property.deprecatedClass
    $this->connection = new MockTestConnection();
    // Access the connection via the property. The property used to be set via a
    // magic method and this can cause problems if coded incorrectly.
    // @phpstan-ignore property.deprecatedClass
    $this->connection->connectionString = 'test://' . urlencode($this->username) . ':' . urlencode($this->password) . "@$this->host:$this->port/";
  }

  /**
   * Copies a file within the jailed environment.
   */
  public function copyFileJailed($source, $destination) {
    // @phpstan-ignore property.deprecatedClass
    $this->connection->run("copyFile $source $destination");
  }

  /**
   * Removes a directory within the jailed environment.
   */
  protected function removeDirectoryJailed($directory) {
    // @phpstan-ignore property.deprecatedClass
    $this->connection->run("rmdir $directory");
  }

  /**
   * Creates a directory within the jailed environment.
   */
  public function createDirectoryJailed($directory) {
    // @phpstan-ignore property.deprecatedClass
    $this->connection->run("mkdir $directory");
  }

  /**
   * Removes a file within the jailed environment.
   */
  public function removeFileJailed($destination) {
    // @phpstan-ignore property.deprecatedClass
    $this->connection->run("rm $destination");
  }

  /**
   * Checks if a path is a directory.
   */
  public function isDirectory($path) {
    return $this->shouldIsDirectoryReturnTrue;
  }

  /**
   * Checks if a path is a file.
   */
  public function isFile($path) {
    return FALSE;
  }

  /**
   * Stub function for changing file permissions within the jailed environment.
   */
  public function chmodJailed($path, $mode, $recursive) {}

}
