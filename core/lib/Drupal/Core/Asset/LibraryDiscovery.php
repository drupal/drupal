<?php

namespace Drupal\Core\Asset;

use Drupal\Core\Cache\CacheCollectorInterface;

/**
 * Discovers available asset libraries in Drupal.
 *
 * @deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use
 * \Drupal\Core\Asset\LibraryDiscoveryCollector instead.
 * @see https://www.drupal.org/node/3462970
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
   * @param \Drupal\Core\Cache\CacheCollectorInterface $library_discovery_collector
   *   The library discovery cache collector.
   */
  public function __construct(CacheCollectorInterface $library_discovery_collector) {
    trigger_error(__CLASS__ . 'is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use LibraryDiscoveryCollector instead. See https://www.drupal.org/node/3462970', E_USER_DEPRECATED);
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
    $libraries = $this->collector->get($extension);
    if (!isset($libraries[$name])) {
      return FALSE;
    }
    if (isset($libraries[$name]['deprecated'])) {
      // phpcs:ignore Drupal.Semantics.FunctionTriggerError
      @trigger_error(str_replace('%library_id%', "$extension/$name", $libraries[$name]['deprecated']), E_USER_DEPRECATED);
    }
    return $libraries[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    $this->collector->clear();
  }

}
