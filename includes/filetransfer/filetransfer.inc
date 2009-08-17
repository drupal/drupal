<?php
// $Id$

/*
 * Base FileTransfer class.
 *
 * Classes extending this class perform file operations on directories not
 * writeable by the webserver. To achieve this, the class should connect back
 * to the server using some backend (for example FTP or SSH). To keep security,
 * the password should always be asked from the user and never stored. For
 * safety, all methods operate only inside a "jail", by default the Drupal root.
 */
abstract class FileTransfer {
  protected $username;
  protected $password;
  protected $hostname = 'localhost';
  protected $port;

  /**
   * The constructer for the UpdateConnection class. This method is also called
   * from the classes that extend this class and override this method.
   */
  function __construct($jail, $username, $password, $hostname, $port) {
    $this->username = $username;
    $this->password = $password;
    $this->hostname = $hostname;
    $this->port = $port;
    $this->jail = $jail;
  }

  abstract static function factory($jail, $settings);

  /**
   * Implementation of the magic __get() method. If the connection isn't set to
   * anything, this will call the connect() method and set it to and return the
   * result; afterwards, the connection will be returned directly without using
   * this method.
   */
  function __get($name) {
    if ($name == 'connection') {
      $this->connect();
      return $this->connection;
    }
  }

  /**
   * Connect to the server.
   */
  abstract protected function connect();

  /**
   * Copies a directory.
   *
   * @param $source
   *   The source path.
   * @param $destination
   *   The destination path.
   */
  public final function copyDirectory($source, $destination) {
    $this->checkPath($destination);
    $this->copyDirectoryJailed($source, $destination);
  }

  /**
   * Creates a directory.
   *
   * @param $directory
   *   The directory to be created.
   */
  public final function createDirectory($directory) {
    $this->checkPath($directory);
    $this->createDirectoryJailed($directory);
  }

  /**
   * Removes a directory.
   *
   * @param $directory
   *   The directory to be removed.
   */
  public final function removeDirectory($directory) {
    $this->checkPath($directory);
    $this->removeDirectoryJailed($directory);
  }

  /**
   * Copies a file.
   *
   * @param $source
   *   The source file.
   * @param $destination
   *   The destination file.
   */
  public final function copyFile($source, $destination) {
    $this->checkPath($destination);
    $this->copyFileJailed($source, $destination);
  }

  /**
   * Removes a file.
   *
   * @param $destination
   *   The destination file to be removed.
   */
  public final function removeFile($destination) {
    $this->checkPath($destination);
    $this->removeFileJailed($destination);
  }

  /**
   * Checks that the path is inside the jail and throws an exception if not.
   *
   * @param $path
   *   A path to check against the jail.
   */
  protected final function checkPath($path) {
    if (drupal_realpath(substr($path, 0, strlen($this->jail))) !== $this->jail) {
      throw new FileTransferException('@directory is outside of the @jail', NULL, array('@directory' => $path, '@jail' => $this->jail));
    }
  }

  /**
   * Copies a directory.
   *
   * We need a separate method to make the $destination is in the jail.
   *
   * @param $source
   *   The source path.
   * @param $destination
   *   The destination path.
   */
  protected function copyDirectoryJailed($source, $destination) {
    if ($this->isDirectory($destination)) {
      $destination = $destination . '/' . basename($source);
    }
    $this->createDirectory($destination);
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST) as $filename => $file) {
      $relative_path = substr($filename, strlen($source));
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
  abstract protected function createDirectoryJailed($directory);

  /**
   * Removes a directory.
   *
   * @param $directory
   *   The directory to be removed.
   */
  abstract protected function removeDirectoryJailed($directory);

  /**
   * Copies a file.
   *
   * @param $source
   *   The source file.
   * @param $destination
   *   The destination file.
   */
  abstract protected function copyFileJailed($source, $destination);

  /**
   * Removes a file.
   *
   * @param $destination
   *   The destination file to be removed.
   */
  abstract protected function removeFileJailed($destination);
  
  /**
   * Checks if a particular path is a directory
   *
   * @param $path
   *   The path to check
   *
   * @return boolean
   */
  abstract public function isDirectory($path);
}

/**
 * FileTransferException class.
 */
class FileTransferException extends Exception {
  public $arguments;

  function __construct($message, $code = 0, $arguments = array()) {
    parent::__construct($message, $code);
    $this->arguments = $arguments;
  }
}
