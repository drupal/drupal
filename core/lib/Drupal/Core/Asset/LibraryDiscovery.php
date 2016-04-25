<?php

namespace Drupal\Core\Asset;

use Drupal\Core\Cache\CacheCollectorInterface;

/**
 * Discovers available asset libraries in Drupal.
 */
class LibraryDiscovery implements LibraryDiscoveryInterface {

  /**
   * The library discovery cache collector.
   *
   * @var \Drupal\Core\Cache\CacheCollectorInterface
   */
  protected $collector;

  /**
   * The final library definitions, statically cached.
   *
   * hook_library_info_alter() and hook_js_settings_alter() allows modules
   * and themes to dynamically alter a library definition (once per request).
   *
   * @var array
   */
  protected $libraryDefinitions = [];

  /**
   * Constructs a new LibraryDiscovery instance.
   *
   * @param \Drupal\Core\Cache\CacheCollectorInterface $library_discovery_collector
   *   The library discovery cache collector.
   */
  public function __construct(CacheCollectorInterface $library_discovery_collector) {
    $this->collector = $library_discovery_collector;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrariesByExtension($extension) {
    if (!isset($this->libraryDefinitions[$extension])) {
      $libraries = $this->collector->get($extension);
      $this->libraryDefinitions[$extension] = [];
      foreach ($libraries as $name => $definition) {
        $this->libraryDefinitions[$extension][$name] = $definition;
      }
    }

    return $this->libraryDefinitions[$extension];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryByName($extension, $name) {
    $extension = $this->getLibrariesByExtension($extension);
    return isset($extension[$name]) ? $extension[$name] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    $this->libraryDefinitions = [];
    $this->collector->clear();
  }

}
