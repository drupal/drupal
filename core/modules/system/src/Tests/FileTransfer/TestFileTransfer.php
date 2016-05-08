<?php

namespace Drupal\system\Tests\FileTransfer;

use Drupal\Core\FileTransfer\FileTransfer;
use Drupal\Core\FileTransfer\FileTransferException;

/**
 * Mock FileTransfer object for test case.
 */
class TestFileTransfer extends FileTransfer {
  protected $host = NULL;
  protected $username = NULL;
  protected $password = NULL;
  protected $port = NULL;

  /**
   * This is for testing the CopyRecursive logic.
   */
  public $shouldIsDirectoryReturnTrue = FALSE;

  function __construct($jail, $username, $password, $hostname = 'localhost', $port = 9999) {
    parent::__construct($jail, $username, $password, $hostname, $port);
  }

  static function factory($jail, $settings) {
    return new TestFileTransfer($jail, $settings['username'], $settings['password'], $settings['hostname'], $settings['port']);
  }

  public function connect() {
    $this->connection = new MockTestConnection();
    $this->connection->connectionString = 'test://' . urlencode($this->username) . ':' . urlencode($this->password) . "@$this->host:$this->port/";
  }

  function copyFileJailed($source, $destination) {
    $this->connection->run("copyFile $source $destination");
  }

  protected function removeDirectoryJailed($directory) {
    $this->connection->run("rmdir $directory");
  }

  function createDirectoryJailed($directory) {
    $this->connection->run("mkdir $directory");
  }

  function removeFileJailed($destination) {
    if (!ftp_delete($this->connection, $item)) {
      throw new FileTransferException('Unable to remove to file @file.', NULL, array('@file' => $item));
    }
  }

  function isDirectory($path) {
    return $this->shouldIsDirectoryReturnTrue;
  }

  function isFile($path) {
    return FALSE;
  }

  function chmodJailed($path, $mode, $recursive) {
    return;
  }

}
