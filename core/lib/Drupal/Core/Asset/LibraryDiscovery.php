<?php

/**
 * @file
 * Contains \Drupal\Core\Asset\LibraryDiscovery.
 */

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
   * Constructs a new LibraryDiscovery instance.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(CacheCollectorInterface $library_discovery_collector) {
    $this->collector = $library_discovery_collector;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrariesByExtension($extension) {
    return $this->collector->get($extension);
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryByName($extension, $name) {
    $extension = $this->getLibrariesByExtension($extension);
    return isset($extension[$name]) ? $extension[$name] : FALSE;
  }

}
