<?php

/*
 * Base FileTransfer class.
 *
 * Classes extending this class perform file operations on directories not
 * writable by the webserver. To achieve this, the class should connect back
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
   * The constructor for the UpdateConnection class. This method is also called
   * from the classes that extend this class and override this method.
   */
  function __construct($jail) {
    $this->jail = $jail;
  }

  /**
   * Classes that extend this class must override the factory() static method.
   *
   * @param string $jail
   *   The full path where all file operations performed by this object will
   *   be restricted to. This prevents the FileTransfer classes from being
   *   able to touch other parts of the filesystem.
   * @param array $settings
   *   An array of connection settings for the FileTransfer subclass. If the
   *   getSettingsForm() method uses any nested settings, the same structure
   *   will be assumed here.
   * @return object
   *   New instance of the appropriate FileTransfer subclass.
   */
  static function factory($jail, $settings) {
    throw new FileTransferException('FileTransfer::factory() static method not overridden by FileTransfer subclass.');
  }

  /**
   * Implementation of the magic __get() method.
   *
   * If the connection isn't set to anything, this will call the connect() method
   * and set it to and return the result; afterwards, the connection will be
   * returned directly without using this method.
   */
  function __get($name) {
    if ($name == 'connection') {
      $this->connect();
      return $this->connection;
    }

    if ($name == 'chroot') {
      $this->setChroot();
      return $this->chroot;
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
    $source = $this->sanitizePath($source);
    $destination = $this->fixRemotePath($destination);
    $this->checkPath($destination);
    $this->copyDirectoryJailed($source, $destination);
  }

  /**
   * @see http://php.net/chmod
   *
   * @param string $path
   * @param long $mode
   * @param bool $recursive
   */
  public final function chmod($path, $mode, $recursive = FALSE) {
    if (!in_array('FileTransferChmodInterface', class_implements(get_class($this)))) {
      throw new FileTransferException('Unable to change file permissions');
    }
    $path = $this->sanitizePath($path);
    $path = $this->fixRemotePath($path);
    $this->checkPath($path);
    $this->chmodJailed($path, $mode, $recursive);
  }

  /**
   * Creates a directory.
   *
   * @param $directory
   *   The directory to be created.
   */
  public final function createDirectory($directory) {
    $directory = $this->fixRemotePath($directory);
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
    $directory = $this->fixRemotePath($directory);
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
    $source = $this->sanitizePath($source);
    $destination = $this->fixRemotePath($destination);
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
    $destination = $this->fixRemotePath($destination);
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
    $full_jail = $this->chroot . $this->jail;
    $full_path = drupal_realpath(substr($this->chroot . $path, 0, strlen($full_jail)));
    $full_path = $this->fixRemotePath($full_path, FALSE);
    if ($full_jail !== $full_path) {
      throw new FileTransferException('@directory is outside of the @jail', NULL, array('@directory' => $path, '@jail' => $this->jail));
    }
  }

  /**
   * Returns a modified path suitable for passing to the server.
   * If a path is a windows path, makes it POSIX compliant by removing the drive letter.
   * If $this->chroot has a value, it is stripped from the path to allow for
   * chroot'd filetransfer systems.
   *
   * @param $path
   * @param $strip_chroot
   *
   * @return string
   */
  protected final function fixRemotePath($path, $strip_chroot = TRUE) {
    $path = $this->sanitizePath($path);
    $path = preg_replace('|^([a-z]{1}):|i', '', $path); // Strip out windows driveletter if its there.
    if ($strip_chroot) {
      if ($this->chroot && strpos($path, $this->chroot) === 0) {
        $path = ($path == $this->chroot) ? '' : substr($path, strlen($this->chroot));
      }
    }
    return $path;
  }

  /**
  * Changes backslashes to slashes, also removes a trailing slash.
  *
  * @param string $path
  * @return string
  */
  function sanitizePath($path) {
    $path = str_replace('\\', '/', $path); // Windows path sanitization.
    if (substr($path, -1) == '/') {
      $path = substr($path, 0, -1);
    }
    return $path;
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
      $destination = $destination . '/' . drupal_basename($source);
    }
    $this->createDirectory($destination);
    foreach (new RecursiveIteratorIterator(new SkipDotsRecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST) as $filename => $file) {
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

  /**
   * Checks if a particular path is a file (not a directory).
   *
   * @param $path
   *   The path to check
   *
   * @return boolean
   */
  abstract public function isFile($path);

  /**
   * Return the chroot property for this connection.
   *
   * It does this by moving up the tree until it finds itself. If successful,
   * it will return the chroot, otherwise FALSE.
   *
   * @return
   *   The chroot path for this connection or FALSE.
   */
  function findChroot() {
    // If the file exists as is, there is no chroot.
    $path = __FILE__;
    $path = $this->fixRemotePath($path, FALSE);
    if ($this->isFile($path)) {
      return FALSE;
    }

    $path = dirname(__FILE__);
    $path = $this->fixRemotePath($path, FALSE);
    $parts = explode('/', $path);
    $chroot = '';
    while (count($parts)) {
      $check = implode($parts, '/');
      if ($this->isFile($check . '/' . drupal_basename(__FILE__))) {
        // Remove the trailing slash.
        return substr($chroot, 0, -1);
      }
      $chroot .= array_shift($parts) . '/';
    }
    return FALSE;
  }

  /**
   * Sets the chroot and changes the jail to match the correct path scheme
   *
   */
  function setChroot() {
    $this->chroot = $this->findChroot();
    $this->jail = $this->fixRemotePath($this->jail);
  }

  /**
   * Returns a form to collect connection settings credentials.
   *
   * Implementing classes can either extend this form with fields collecting the
   * specific information they need, or override it entirely.
   */
  public function getSettingsForm() {
    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
    );
    $form['password'] = array(
      '#type' => 'password',
      '#title' => t('Password'),
      '#description' => t('Your password is not saved in the database and is only used to establish a connection.'),
    );
    $form['advanced'] = array(
      '#type' => 'fieldset',
      '#title' => t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['advanced']['hostname'] = array(
      '#type' => 'textfield',
      '#title' => t('Host'),
      '#default_value' => 'localhost',
      '#description' => t('The connection will be created between your web server and the machine hosting the web server files. In the vast majority of cases, this will be the same machine, and "localhost" is correct.'),
    );
    $form['advanced']['port'] = array(
      '#type' => 'textfield',
      '#title' => t('Port'),
      '#default_value' => NULL,
    );
    return $form;
  }
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


/**
 * A FileTransfer Class implementing this interface can be used to chmod files.
 */
interface FileTransferChmodInterface {

  /**
   * Changes the permissions of the file / directory specified in $path
   *
   * @param string $path
   *   Path to change permissions of.
   * @param long $mode
   *   The new file permission mode to be passed to chmod().
   * @param boolean $recursive
   *   Pass TRUE to recursively chmod the entire directory specified in $path.
   */
  function chmodJailed($path, $mode, $recursive);
}

/**
 * Provides an interface for iterating recursively over filesystem directories.
 *
 * Manually skips '.' and '..' directories, since no existing method is
 * available in PHP 5.2.
 *
 * @todo Depreciate in favor of RecursiveDirectoryIterator::SKIP_DOTS once PHP
 *   5.3 or later is required.
 */
class SkipDotsRecursiveDirectoryIterator extends RecursiveDirectoryIterator {
  /**
   * Constructs a SkipDotsRecursiveDirectoryIterator
   *
   * @param $path
   *   The path of the directory to be iterated over.
   */
  function __construct($path) {
    parent::__construct($path);
  }

  function next() {
    parent::next();
    while ($this->isDot()) {
      parent::next();
    }
  }
}
