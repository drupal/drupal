<?php

namespace Drupal\Component\Discovery;

/**
 * Discovers multiple YAML files in a set of directories and sub-directories.
 */
class YamlRecursiveDirectoryDiscovery extends YamlDirectoryDiscovery {

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

}
