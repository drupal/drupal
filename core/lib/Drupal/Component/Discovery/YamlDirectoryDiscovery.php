<?php

namespace Drupal\Component\Discovery;

use Drupal\Component\FileSystem\RegexDirectoryIterator;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\FileCache\FileCacheFactory;

/**
 * Discovers multiple YAML files in a set of directories.
 */
class YamlDirectoryDiscovery implements DiscoverableInterface {

  /**
   * Defines the key in the discovered data where the file path is stored.
   */
  const FILE_KEY = '_discovered_file_path';

  /**
   * An array of directories to scan, keyed by the provider.
   *
   * The value can either be a string or an array of strings. The string values
   * should be the path of a directory to scan.
   *
   * @var array
   */
  protected $directories = [];

  /**
   * The suffix for the file cache key.
   *
   * @var string
   */
  protected $fileCacheKeySuffix;

  /**
   * The key contained in the discovered data that identifies it.
   *
   * @var string
   */
  protected $idKey;

  /**
   * Constructs a YamlDirectoryDiscovery object.
   *
   * @param array $directories
   *   An array of directories to scan, keyed by the provider. The value can
   *   either be a string or an array of strings. The string values should be
   *   the path of a directory to scan.
   * @param string $file_cache_key_suffix
   *   The file cache key suffix. This should be unique for each type of
   *   discovery.
   * @param string $key
   *   (optional) The key contained in the discovered data that identifies it.
   *   Defaults to 'id'.
   */
  public function __construct(array $directories, $file_cache_key_suffix, $key = 'id') {
    $this->directories = $directories;
    $this->fileCacheKeySuffix = $file_cache_key_suffix;
    $this->idKey = $key;
  }

  /**
   * {@inheritdoc}
   */
  public function findAll() {
    $all = array();

    $files = $this->findFiles();

    $file_cache = FileCacheFactory::get('yaml_discovery:' . $this->fileCacheKeySuffix);

    // Try to load from the file cache first.
    foreach ($file_cache->getMultiple(array_keys($files)) as $file => $data) {
      $all[$files[$file]][$this->getIdentifier($file, $data)] = $data;
      unset($files[$file]);
    }

    // If there are files left that were not returned from the cache, load and
    // parse them now. This list was flipped above and is keyed by filename.
    if ($files) {
      foreach ($files as $file => $provider) {
        // If a file is empty or its contents are commented out, return an empty
        // array instead of NULL for type consistency.
        try {
          $data = Yaml::decode(file_get_contents($file)) ?: [];
        }
        catch (InvalidDataTypeException $e) {
          throw new DiscoveryException("The $file contains invalid YAML", 0, $e);
        }
        $data[static::FILE_KEY] = $file;
        $all[$provider][$this->getIdentifier($file, $data)] = $data;
        $file_cache->set($file, $data);
      }
    }

    return $all;
  }

  /**
   * Gets the identifier from the data.
   *
   * @param string $file
   *   The filename.
   * @param array $data
   *   The data from the YAML file.
   *
   * @return string
   *   The identifier from the data.
   */
  protected function getIdentifier($file, array $data) {
    if (!isset($data[$this->idKey])) {
      throw new DiscoveryException("The $file contains no data in the identifier key '{$this->idKey}'");
    }
    return $data[$this->idKey];
  }

  /**
   * Returns an array of providers keyed by file path.
   *
   * @return array
   *   An array of providers keyed by file path.
   */
  protected function findFiles() {
    $file_list = [];
    foreach ($this->directories as $provider => $directories) {
      $directories = (array) $directories;
      foreach ($directories as $directory) {
        if (is_dir($directory)) {
          /** @var \SplFileInfo $fileInfo */
          foreach ($this->getDirectoryIterator($directory) as $fileInfo) {
            $file_list[$fileInfo->getPathname()] = $provider;
          }
        }
      }
    }
    return $file_list;
  }

  /**
   * Gets an iterator to loop over the files in the provided directory.
   *
   * This method exists so that it is easy to replace this functionality in a
   * class that extends this one. For example, it could be used to make the scan
   * recursive.
   *
   * @param string $directory
   *   The directory to scan.
   *
   * @return \Traversable
   *   An \Traversable object or array where the values are \SplFileInfo
   *   objects.
   */
  protected function getDirectoryIterator($directory) {
    return new RegexDirectoryIterator($directory, '/\.yml$/i');
  }

}
