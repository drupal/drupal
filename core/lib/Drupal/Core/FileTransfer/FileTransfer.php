<?php

namespace Drupal\Core\FileTransfer;

/**
 * Defines the base FileTransfer class.
 *
 * Classes extending this class perform file operations on directories not
 * writable by the webserver. To achieve this, the class should connect back
 * to the server using some backend (for example FTP or SSH). To keep security,
 * the password should always be asked from the user and never stored. For
 * safety, all methods operate only inside a "jail", by default the Drupal root.
 */
abstract class FileTransfer {

  /**
   * The username for this file transfer.
   *
   * @var string
   */
  protected $username;

  /**
   * The password for this file transfer.
   *
   * @var string
   */
  protected $password;

  /**
   * The hostname for this file transfer.
   *
   * @var string
   */
  protected $hostname = 'localhost';

  /**
   * The port for this file transfer.
   *
   * @var int
   */
  protected $port;

  /**
   * Constructs a Drupal\Core\FileTransfer\FileTransfer object.
   *
   * @param $jail
   *   The full path where all file operations performed by this object will
   *   be restricted to. This prevents the FileTransfer classes from being
   *   able to touch other parts of the filesystem.
   */
  public function __construct($jail) {
    $this->jail = $jail;
  }

  /**
   * Defines a factory method for this class.
   *
   * Classes that extend this class must override the factory() static method.
   * They should return a new instance of the appropriate FileTransfer subclass.
   *
   * @param string $jail
   *   The full path where all file operations performed by this object will
   *   be restricted to. This prevents the FileTransfer classes from being
   *   able to touch other parts of the filesystem.
   * @param array $settings
   *   An array of connection settings for the FileTransfer subclass. If the
   *   getSettingsForm() method uses any nested settings, the same structure
   *   will be assumed here.
   *
   * @return object
   *   New instance of the appropriate FileTransfer subclass.
   *
   * @throws \Drupal\Core\FileTransfer\FileTransferException
   */
  public static function factory($jail, $settings) {
    throw new FileTransferException('FileTransfer::factory() static method not overridden by FileTransfer subclass.');
  }

  /**
   * Implements the magic __get() method.
   *
   * If the connection isn't set to anything, this will call the connect()
   * method and return the result; afterwards, the connection will be returned
   * directly without using this method.
   *
   * @param string $name
   *   The name of the variable to return.
   *
   * @return string|bool
   *   The variable specified in $name.
   */
  public function __get($name) {
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
   * Connects to the server.
   */
  abstract public function connect();

  /**
   * Copies a directory.
   *
   * @param string $source
   *   The source path.
   * @param string $destination
   *   The destination path.
   */
  final public function copyDirectory($source, $destination) {
    $source = $this->sanitizePath($source);
    $destination = $this->fixRemotePath($destination);
    $this->checkPath($destination);
    $this->copyDirectoryJailed($source, $destination);
  }

  /**
   * Changes the permissions of the specified $path (file or directory).
   *
   * @param string $path
   *   The file / directory to change the permissions of.
   * @param int $mode
   *   The new file permission mode to be passed to chmod().
   * @param bool $recursive
   *   Pass TRUE to recursively chmod the entire directory specified in $path.
   *
   * @throws \Drupal\Core\FileTransfer\FileTransferException
   *
   * @see http://php.net/chmod
   */
  final public function chmod($path, $mode, $recursive = FALSE) {
    if (!($this instanceof ChmodInterface)) {
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
   * @param string $directory
   *   The directory to be created.
   */
  final public function createDirectory($directory) {
    $directory = $this->fixRemotePath($directory);
    $this->checkPath($directory);
    $this->createDirectoryJailed($directory);
  }

  /**
   * Removes a directory.
   *
   * @param string $directory
   *   The directory to be removed.
   */
  final public function removeDirectory($directory) {
    $directory = $this->fixRemotePath($directory);
    $this->checkPath($directory);
    $this->removeDirectoryJailed($directory);
  }

  /**
   * Copies a file.
   *
   * @param string $source
   *   The source file.
   * @param string $destination
   *   The destination file.
   */
  final public function copyFile($source, $destination) {
    $source = $this->sanitizePath($source);
    $destination = $this->fixRemotePath($destination);
    $this->checkPath($destination);
    $this->copyFileJailed($source, $destination);
  }

  /**
   * Removes a file.
   *
   * @param string $destination
   *   The destination file to be removed.
   */
  final public function removeFile($destination) {
    $destination = $this->fixRemotePath($destination);
    $this->checkPath($destination);
    $this->removeFileJailed($destination);
  }

  /**
   * Checks that the path is inside the jail and throws an exception if not.
   *
   * @param string $path
   *   A path to check against the jail.
   *
   * @throws \Drupal\Core\FileTransfer\FileTransferException
   */
  final protected function checkPath($path) {
    $full_jail = $this->chroot . $this->jail;
    $full_path = \Drupal::service('file_system')
      ->realpath(substr($this->chroot . $path, 0, strlen($full_jail)));
    $full_path = $this->fixRemotePath($full_path, FALSE);
    if ($full_jail !== $full_path) {
      throw new FileTransferException('@directory is outside of the @jail', NULL, ['@directory' => $path, '@jail' => $this->jail]);
    }
  }

  /**
   * Returns a modified path suitable for passing to the server.
   *
   * If a path is a windows path, makes it POSIX compliant by removing the drive
   * letter. If $this->chroot has a value and $strip_chroot is TRUE, it is
   * stripped from the path to allow for chroot'd filetransfer systems.
   *
   * @param string $path
   *   The path to modify.
   * @param bool $strip_chroot
   *   Whether to remove the path in $this->chroot.
   *
   * @return string
   *   The modified path.
   */
  final protected function fixRemotePath($path, $strip_chroot = TRUE) {
    $path = $this->sanitizePath($path);
    // Strip out windows driveletter if its there.
    $path = preg_replace('|^([a-z]{1}):|i', '', $path);
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
   *   The path to modify.
   *
   * @return string
   *   The modified path.
   */
  public function sanitizePath($path) {
    // Windows path sanitization.
    $path = str_replace('\\', '/', $path);
    if (substr($path, -1) == '/') {
      $path = substr($path, 0, -1);
    }
    return $path;
  }

  /**
   * Copies a directory.
   *
   * We need a separate method to make sure the $destination is in the jail.
   *
   * @param string $source
   *   The source path.
   * @param string $destination
   *   The destination path.
   */
  protected function copyDirectoryJailed($source, $destination) {
    if ($this->isDirectory($destination)) {
      $destination = $destination . '/' . drupal_basename($source);
    }
    $this->createDirectory($destination);
    foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $filename => $file) {
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
   * @param string $directory
   *   The directory to be created.
   */
  abstract protected function createDirectoryJailed($directory);

  /**
   * Removes a directory.
   *
   * @param string $directory
   *   The directory to be removed.
   */
  abstract protected function removeDirectoryJailed($directory);

  /**
   * Copies a file.
   *
   * @param string $source
   *   The source file.
   * @param string $destination
   *   The destination file.
   */
  abstract protected function copyFileJailed($source, $destination);

  /**
   * Removes a file.
   *
   * @param string $destination
   *   The destination file to be removed.
   */
  abstract protected function removeFileJailed($destination);

  /**
   * Checks if a particular path is a directory.
   *
   * @param string $path
   *   The path to check
   *
   * @return bool
   *   TRUE if the specified path is a directory, FALSE otherwise.
   */
  abstract public function isDirectory($path);

  /**
   * Checks if a particular path is a file (not a directory).
   *
   * @param string $path
   *   The path to check.
   *
   * @return bool
   *   TRUE if the specified path is a file, FALSE otherwise.
   */
  abstract public function isFile($path);

  /**
   * Returns the chroot property for this connection.
   *
   * It does this by moving up the tree until it finds itself
   *
   * @return string|bool
   *   If successful, the chroot path for this connection, otherwise FALSE.
   */
  public function findChroot() {
    // If the file exists as is, there is no chroot.
    $path = __FILE__;
    $path = $this->fixRemotePath($path, FALSE);
    if ($this->isFile($path)) {
      return FALSE;
    }

    $path = __DIR__;
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
   * Sets the chroot and changes the jail to match the correct path scheme.
   */
  public function setChroot() {
    $this->chroot = $this->findChroot();
    $this->jail = $this->fixRemotePath($this->jail);
  }

  /**
   * Returns a form to collect connection settings credentials.
   *
   * Implementing classes can either extend this form with fields collecting the
   * specific information they need, or override it entirely.
   *
   * @return array
   *   An array that contains a Form API definition.
   */
  public function getSettingsForm() {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => t('Username'),
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => t('Password'),
      '#description' => t('Your password is not saved in the database and is only used to establish a connection.'),
    ];
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => t('Advanced settings'),
    ];
    $form['advanced']['hostname'] = [
      '#type' => 'textfield',
      '#title' => t('Host'),
      '#default_value' => 'localhost',
      '#description' => t('The connection will be created between your web server and the machine hosting the web server files. In the vast majority of cases, this will be the same machine, and "localhost" is correct.'),
    ];
    $form['advanced']['port'] = [
      '#type' => 'textfield',
      '#title' => t('Port'),
      '#default_value' => NULL,
    ];
    return $form;
  }

}
