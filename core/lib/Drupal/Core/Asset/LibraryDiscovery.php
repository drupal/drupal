<?php

/**
 * @file
 * Contains \Drupal\Core\Asset\LibraryDiscovery.
 */

namespace Drupal\Core\Asset;

use Drupal\Core\Cache\CacheCollectorInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;

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
   * The cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

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
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tag_invalidator
   *   The cache tag invalidator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(CacheCollectorInterface $library_discovery_collector, CacheTagsInvalidatorInterface $cache_tag_invalidator) {
    $this->collector = $library_discovery_collector;
    $this->cacheTagInvalidator = $cache_tag_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibrariesByExtension($extension) {
    if (!isset($this->libraryDefinitions[$extension])) {
      $libraries = $this->collector->get($extension);
      $this->libraryDefinitions[$extension] = [];
      foreach ($libraries as $name => $definition) {
        $library_name = "$extension/$name";
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
    $this->cacheTagInvalidator->invalidateTags(['library_info']);
  }

}
