<?php

namespace Drupal\Component\Discovery;

/**
 * Discovers multiple YAML files in a set of directories and sub-directories.
 */
class YamlRecursiveDirectoryDiscovery extends YamlDirectoryDiscovery {

  /**
   * The regex pattern used to exclude files or directories.
   *
   * @var string
   */
  protected $excludePattern;

  /**
   * Constructs a YamlRecursiveDirectoryDiscovery object.
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
   * @param string $exclude_pattern
   *   (optional) The regexp pattern used to exclude discovered files.
   */
  public function __construct(array $directories, $file_cache_key_suffix, $key = 'id', $exclude_pattern = '') {
    parent::__construct($directories, $file_cache_key_suffix, $key);
    $this->excludePattern = $exclude_pattern;
  }

  /**
   * Gets an iterator to loop over the files in the provided directory.
   *
   * It'll loop over given directory and their sub-directories recursively.
   *
   * @param string $directory
   *   The directory to scan.
   *
   * @return \Traversable
   *   An \Traversable object or array where the values are \SplFileInfo
   *   objects.
   */
  protected function getDirectoryIterator($directory) {
    $dir_iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
    return new \RegexIterator($dir_iterator, '/\.yml$/i');
  }

  /**
   * Returns an array of providers keyed by file path.
   *
   * The files are filtered by exclude pattern, if provided.
   *
   * @return array
   *   An array of providers keyed by file path.
   */
  protected function findFiles() {
    $file_list = parent::findFiles();

    if (empty($this->excludePattern)) {
      return $file_list;
    }

    foreach ($file_list as $path => $provider) {
      if (preg_match($this->excludePattern, $path)) {
        unset($file_list[$path]);
      }
    }
    return $file_list;
  }

}
