<?php

namespace Drupal\Core\Plugin\Discovery;

use Drupal\Component\Discovery\YamlDirectoryDiscovery;
use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
use Drupal\Core\File\FileSystemInterface;

/**
 * Does the actual finding of the directories with metadata files.
 */
class DirectoryWithMetadataDiscovery extends YamlDirectoryDiscovery {

  use DeprecatedServicePropertyTrait;

  /**
   * The deprecated properties.
   *
   * @see https://www.drupal.org/node/3530869
   */
  protected array $deprecatedProperties = [
    'fileSystem' => FileSystemInterface::class,
  ];

  /**
   * Constructs a DirectoryWithMetadataDiscovery object.
   *
   * @param array $directories
   *   An array of directories to scan, keyed by the provider. The value can
   *   either be a string or an array of strings. The string values should be
   *   the path of a directory to scan.
   * @param string $file_cache_key_suffix
   *   The file cache key suffix. This should be unique for each type of
   *   discovery.
   * @param \Drupal\Core\File\FileSystemInterface|null $fileSystem
   *   The file system service.
   */
  public function __construct(array $directories, string $file_cache_key_suffix, ?FileSystemInterface $fileSystem = NULL) {
    parent::__construct($directories, $file_cache_key_suffix);
    if ($fileSystem) {
      @trigger_error(sprintf('Passing the $fileSystem parameter to %s() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. The class no longer uses the file system service. See https://www.drupal.org/node/3530869', __METHOD__), E_USER_DEPRECATED);
    }
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
   * @return \RecursiveIteratorIterator
   *   A \RecursiveIteratorIterator object or array where the values are
   *   \SplFileInfo objects.
   */
  protected function getDirectoryIterator($directory): \RecursiveIteratorIterator {
    // Use FilesystemIterator to not iterate over the . and .. directories.
    $flags = \FilesystemIterator::KEY_AS_PATHNAME
      | \FilesystemIterator::CURRENT_AS_FILEINFO
      | \FilesystemIterator::SKIP_DOTS;
    $directory_iterator = new \RecursiveDirectoryIterator($directory, $flags);
    // Detect "my_component.component.yml".
    $regex = '/^([a-z0-9_-])+\.component\.yml$/i';
    $filter = new RegexRecursiveFilterIterator($directory_iterator, $regex);
    return new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::LEAVES_ONLY, $flags);
  }

  /**
   * {@inheritdoc}
   *
   * The IDs can collide in two different scenarios:
   *
   * 1. Because one component is overriding another one via "weight".
   * 2. Because the same component exists in different themes.
   */
  protected function getIdentifier($file, array $data): string {
    $id = basename($file, '.component.yml');
    $provider_paths = array_flip($this->directories);
    $provider = $this->findProvider($file, $provider_paths);
    // We use the provider to dedupe components because it does not make sense
    // for a single provider to fork itself.
    return sprintf('%s:%s', $provider, $id);
  }

  /**
   * Finds the provider of the discovered file.
   *
   * The approach here is suboptimal because the provider is actually set in
   * the plugin definition after the getIdentifier is called. So we either do
   * this, or we forego the base class.
   *
   * @param string $file
   *   The discovered file.
   * @param array $provider_paths
   *   The associative array of the path to the provider.
   *
   * @return string
   *   The provider
   */
  private function findProvider(string $file, array $provider_paths): string {
    $parts = explode(DIRECTORY_SEPARATOR, $file);
    array_pop($parts);
    if (empty($parts)) {
      return '';
    }
    $provider = $provider_paths[implode(DIRECTORY_SEPARATOR, $parts)] ?? '';
    return empty($provider)
      ? $this->findProvider(implode(DIRECTORY_SEPARATOR, $parts), $provider_paths)
      : $provider;
  }

}
