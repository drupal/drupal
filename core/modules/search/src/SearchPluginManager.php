<?php

namespace Drupal\search;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\search\Attribute\Search;

/**
 * SearchExecute plugin manager.
 */
class SearchPluginManager extends DefaultPluginManager {

  /**
   * Constructs SearchPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Search', $namespaces, $module_handler, 'Drupal\search\Plugin\SearchInterface', Search::class, 'Drupal\search\Annotation\SearchPlugin');
    $this->setCacheBackend($cache_backend, 'search_plugins');
    $this->alterInfo('search_plugin');
  }

}
