<?php
// $Id$

/*
 * Connection class.
 *
 * This class does file operations on directories not writeable by the
 * webserver. It connects back to the server using some backend (for example
 * FTP or SSH). To keep security the password should always be asked from the
 * user and never stored.
 */
abstract class FileTransfer {

  /**
   * The constructer for the UpdateConnection class. This method is also called
   * from the classes that extend this class and override this method.
   */
  function __construct($settings) {
    $this->username = $settings['username'];
    $this->password = $settings['password'];
    $this->hostname = isset($settings['hostname']) ? $settings['hostname'] : 'localhost';
    if (isset($settings['port'])) {
      $this->port = $settings['port'];
    }
  }

  /**
   * Implementation of the magic __get() method. If the connection isn't set to
   * anything, this will call the connect() method and set it to and return the
   * result; afterwards, the connection will be returned directly without using
   * this method.
   */
  function __get($name) {
    static $connection;
    if ($name == 'connection') {
      $this->connection = $this->connect();
      return $this->connection;
    }
  }

  /**
   * Copies a directory.
   *
   * @param $source
   *   The source path.
   * @param $destination
   *   The destination path.
   */
  protected function copyDirectory($source, $destination) {
    $this->createDirectory($destination . basename($source));
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST) as $filename => $file) {
      $relative_path = basename($source) . substr($filename, strlen($source));
      if ($file->isDir()) {
        $this->createDirectory($destination . $relative_path);
      }
      else {
        $this->copyFile($file->getPathName(), $destination . $relative_path);
      }
    }
  }

  /**
   * Creates a directory.
   *
   * @param $directory
   *   The directory to be created.
   */
  abstract function createDirectory($directory);

  /**
   * Removes a directory.
   *
   * @param $directory
   *   The directory to be removed.
   */
  abstract function removeDirectory($directory);

  /**
   * Copies a file.
   *
   * @param $source
   *   The source file.
   * @param $destination
   *   The destination file.
   */
  abstract function copyFile($source, $destination);


  /**
   * Removes a file.
   *
   * @param $destination
   *   The destination file to be removed.
   */
  abstract function removeFile($destination);
}

/**
 * FileTransferException class.
 */
class FileTransferException extends Exception {
}
