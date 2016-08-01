<?php

namespace Drupal\Core\Config;

use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Serialization\Yaml;

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
   * The file cache object.
   *
   * @var \Drupal\Component\FileCache\FileCacheInterface
   */
  protected $fileCache;

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

    // Use a NULL File Cache backend by default. This will ensure only the
    // internal statc caching of FileCache is used and thus avoids blowing up
    // the APCu cache.
    $this->fileCache = FileCacheFactory::get('config', ['cache_backend_class' => NULL]);
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
   * {@inheritdoc}
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

    $filepath = $this->getFilePath($name);
    if ($data = $this->fileCache->get($filepath)) {
      return $data;
    }

    $data = file_get_contents($filepath);
    try {
      $data = $this->decode($data);
    }
    catch (InvalidDataTypeException $e) {
      throw new UnsupportedDataTypeConfigException('Invalid data type in config ' . $name . ', found in file' . $filepath . ' : ' . $e->getMessage());
    }
    $this->fileCache->set($filepath, $data);

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
      $encoded_data = $this->encode($data);
    }
    catch (InvalidDataTypeException $e) {
      throw new StorageException("Invalid data type in config $name: {$e->getMessage()}");
    }

    $target = $this->getFilePath($name);
    $status = @file_put_contents($target, $encoded_data);
    if ($status === FALSE) {
      // Try to make sure the directory exists and try writing again.
      $this->ensureStorage();
      $status = @file_put_contents($target, $encoded_data);
    }
    if ($status === FALSE) {
      throw new StorageException('Failed to write configuration file: ' . $this->getFilePath($name));
    }
    else {
      drupal_chmod($target);
    }

    $this->fileCache->set($target, $data);

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    if (!$this->exists($name)) {
      $dir = $this->getCollectionDirectory();
      if (!file_exists($dir)) {
        throw new StorageException($dir . '/ not found.');
      }
      return FALSE;
    }
    $this->fileCache->delete($this->getFilePath($name));
    return drupal_unlink($this->getFilePath($name));
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    $status = @rename($this->getFilePath($name), $this->getFilePath($new_name));
    if ($status === FALSE) {
      throw new StorageException('Failed to rename configuration file from: ' . $this->getFilePath($name) . ' to: ' . $this->getFilePath($new_name));
    }
    $this->fileCache->delete($this->getFilePath($name));
    $this->fileCache->delete($this->getFilePath($new_name));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    return Yaml::encode($data);
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    $dir = $this->getCollectionDirectory();
    if (!is_dir($dir)) {
      return array();
    }
    $extension = '.' . static::getFileExtension();

    // glob() directly calls into libc glob(), which is not aware of PHP stream
    // wrappers. Same for \GlobIterator (which additionally requires an absolute
    // realpath() on Windows).
    // @see https://github.com/mikey179/vfsStream/issues/2
    $files = scandir($dir);

    $names = array();
    $pattern = '/^' . preg_quote($prefix, '/') . '.*' . preg_quote($extension, '/') . '$/';
    foreach ($files as $file) {
      if ($file[0] !== '.' && preg_match($pattern, $file)) {
        $names[] = basename($file, $extension);
      }
    }

    return $names;
  }

  /**
   * {@inheritdoc}
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
    $pattern = '/\.' . preg_quote($this->getFileExtension(), '/') . '$/';
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
        // Check that the collection is valid by searching it for configuration
        // objects. A directory without any configuration objects is not a valid
        // collection.
        // @see \Drupal\Core\Config\FileStorage::listAll()
        foreach (scandir($directory . '/' . $collection) as $file) {
          if ($file[0] !== '.' && preg_match($pattern, $file)) {
            $collections[] = $collection;
            break;
          }
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
