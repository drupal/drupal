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

  public static function factory($jail, $settings) {
    assert(is_array($settings));
    return new TestFileTransfer($jail);
  }

  public function connect() {
    $this->connection = new MockTestConnection();
    // Access the connection via the property. The property used to be set via a
    // magic method and this can cause problems if coded incorrectly.
    $this->connection->connectionString = 'test://' . urlencode($this->username) . ':' . urlencode($this->password) . "@$this->host:$this->port/";
  }

  public function copyFileJailed($source, $destination) {
    $this->connection->run("copyFile $source $destination");
  }

  protected function removeDirectoryJailed($directory) {
    $this->connection->run("rmdir $directory");
  }

  public function createDirectoryJailed($directory) {
    $this->connection->run("mkdir $directory");
  }

  public function removeFileJailed($destination) {
    $this->connection->run("rm $destination");
  }

  public function isDirectory($path) {
    return $this->shouldIsDirectoryReturnTrue;
  }

  public function isFile($path) {
    return FALSE;
  }

  public function chmodJailed($path, $mode, $recursive) {}

}
