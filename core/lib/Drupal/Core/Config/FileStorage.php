<?php

/**
 * @file
 * Contains \Drupal\Core\Config\FileStorage.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Defines the file storage.
 */
class FileStorage implements StorageInterface {

  /**
   * The storage collection.
   *
   * @var string
   */
  protected $collection;

  /**
   * The filesystem path for configuration objects.
   *
   * @var string
   */
  protected $directory = '';

  /**
   * Constructs a new FileStorage.
   *
   * @param string $directory
   *   A directory path to use for reading and writing of configuration files.
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection.
   */
  public function __construct($directory, $collection = StorageInterface::DEFAULT_COLLECTION) {
    $this->directory = $directory;
    $this->collection = $collection;
  }

  /**
   * Returns the path to the configuration file.
   *
   * @return string
   *   The path to the configuration file.
   */
  public function getFilePath($name) {
    return $this->getCollectionDirectory() . '/' . $name . '.' . static::getFileExtension();
  }

  /**
   * Returns the file extension used by the file storage for all configuration files.
   *
   * @return string
   *   The file extension.
   */
  public static function getFileExtension() {
    return 'yml';
  }

  /**
   * Check if the directory exists and create it if not.
   */
  protected function ensureStorage() {
    $dir = $this->getCollectionDirectory();
    $success = file_prepare_directory($dir, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    // Only create .htaccess file in root directory.
    if ($dir == $this->directory) {
      $success = $success && file_save_htaccess($this->directory, TRUE, TRUE);
    }
    if (!$success) {
      throw new StorageException('Failed to create config directory ' . $dir);
    }
    return $this;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::exists().
   */
  public function exists($name) {
    return file_exists($this->getFilePath($name));
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::read().
   *
   * @throws \Drupal\Core\Config\UnsupportedDataTypeConfigException
   */
  public function read($name) {
    if (!$this->exists($name)) {
      return FALSE;
    }
    $data = file_get_contents($this->getFilePath($name));
    try {
      $data = $this->decode($data);
    }
    catch (InvalidDataTypeException $e) {
      throw new UnsupportedDataTypeConfigException(SafeMarkup::format('Invalid data type in config @name: !message', array(
        '@name' => $name,
        '!message' => $e->getMessage(),
      )));
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $list = array();
    foreach ($names as $name) {
      if ($data = $this->read($name)) {
        $list[$name] = $data;
      }
    }
    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    try {
      $data = $this->encode($data);
    }
    catch (InvalidDataTypeException $e) {
      throw new StorageException(SafeMarkup::format('Invalid data type in config @name: !message', array(
        '@name' => $name,
        '!message' => $e->getMessage(),
      )));
    }

    $target = $this->getFilePath($name);
    $status = @file_put_contents($target, $data);
    if ($status === FALSE) {
      // Try to make sure the directory exists and try writing again.
      $this->ensureStorage();
      $status = @file_put_contents($target, $data);
    }
    if ($status === FALSE) {
      throw new StorageException('Failed to write configuration file: ' . $this->getFilePath($name));
    }
    else {
      drupal_chmod($target);
    }
    return TRUE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::delete().
   */
  public function delete($name) {
    if (!$this->exists($name)) {
      $dir = $this->getCollectionDirectory();
      if (!file_exists($dir)) {
        throw new StorageException($dir . '/ not found.');
      }
      return FALSE;
    }
    return drupal_unlink($this->getFilePath($name));
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::rename().
   */
  public function rename($name, $new_name) {
    $status = @rename($this->getFilePath($name), $this->getFilePath($new_name));
    if ($status === FALSE) {
      throw new StorageException('Failed to rename configuration file from: ' . $this->getFilePath($name) . ' to: ' . $this->getFilePath($new_name));
    }
    return TRUE;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::encode().
   */
  public function encode($data) {
    return Yaml::encode($data);
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::decode().
   */
  public function decode($raw) {
    $data = Yaml::decode($raw);
    // A simple string is valid YAML for any reason.
    if (!is_array($data)) {
      return FALSE;
    }
    return $data;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::listAll().
   */
  public function listAll($prefix = '') {
    // glob() silently ignores the error of a non-existing search directory,
    // even with the GLOB_ERR flag.
    $dir = $this->getCollectionDirectory();
    if (!file_exists($dir)) {
      return array();
    }
    $extension = '.' . static::getFileExtension();
    // \GlobIterator on Windows requires an absolute path.
    $files = new \GlobIterator(realpath($dir) . '/' . $prefix . '*' . $extension);

    $names = array();
    foreach ($files as $file) {
      $names[] = $file->getBasename($extension);
    }

    return $names;
  }

  /**
   * Implements Drupal\Core\Config\StorageInterface::deleteAll().
   */
  public function deleteAll($prefix = '') {
    $success = TRUE;
    $files = $this->listAll($prefix);
    foreach ($files as $name) {
      if (!$this->delete($name) && $success) {
        $success = FALSE;
      }
    }
    if ($success && $this->collection != StorageInterface::DEFAULT_COLLECTION) {
      // Remove empty directories.
      if (!(new \FilesystemIterator($this->getCollectionDirectory()))->valid()) {
        drupal_rmdir($this->getCollectionDirectory());
      }
    }
    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    return new static(
      $this->directory,
      $collection
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    return $this->collection;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    $collections = $this->getAllCollectionNamesHelper($this->directory);
    sort($collections);
    return $collections;
  }

  /**
   * Helper function for getAllCollectionNames().
   *
   * If the file storage has the following subdirectory structure:
   *   ./another_collection/one
   *   ./another_collection/two
   *   ./collection/sub/one
   *   ./collection/sub/two
   * this function will return:
   * @code
   *   array(
   *     'another_collection.one',
   *     'another_collection.two',
   *     'collection.sub.one',
   *     'collection.sub.two',
   *   );
   * @endcode
   *
   * @param string $directory
   *   The directory to check for sub directories. This allows this
   *   function to be used recursively to discover all the collections in the
   *   storage.
   *
   * @return array
   *   A list of collection names contained within the provided directory.
   */
  protected function getAllCollectionNamesHelper($directory) {
    $collections = array();
    foreach (new \DirectoryIterator($directory) as $fileinfo) {
      if ($fileinfo->isDir() && !$fileinfo->isDot()) {
        $collection = $fileinfo->getFilename();
        // Recursively call getAllCollectionNamesHelper() to discover if there
        // are subdirectories. Subdirectories represent a dotted collection
        // name.
        $sub_collections = $this->getAllCollectionNamesHelper($directory . '/' . $collection);
        if (!empty($sub_collections)) {
          // Build up the collection name by concatenating the subdirectory
          // names with the current directory name.
          foreach ($sub_collections as $sub_collection) {
            $collections[] = $collection . '.' . $sub_collection;
          }
        }
        // Check that the collection is valid by searching if for configuration
        // objects. A directory without any configuration objects is not a valid
        // collection.
        // \GlobIterator on Windows requires an absolute path.
        $files = new \GlobIterator(realpath($directory . '/' . $collection) . '/*.' . $this->getFileExtension());
        if (count($files)) {
          $collections[] = $collection;
        }
      }
    }
    return $collections;
  }

  /**
   * Gets the directory for the collection.
   *
   * @return string
   *   The directory for the collection.
   */
  protected function getCollectionDirectory() {
    if ($this->collection == StorageInterface::DEFAULT_COLLECTION) {
      $dir = $this->directory;
    }
    else {
      $dir = $this->directory . '/' . str_replace('.', '/', $this->collection);
    }
    return $dir;
  }

}
