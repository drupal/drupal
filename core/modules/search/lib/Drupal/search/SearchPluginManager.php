<?php

/**
 * @file
 * Contains \Drupal\search\SearchPluginManager.
 */

namespace Drupal\search;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageManager;

/**
 * SearchExecute plugin manager.
 */
class SearchPluginManager extends DefaultPluginManager {

  /**
   * Constructs SearchPluginManager
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Search', $namespaces, $module_handler, 'Drupal\search\Annotation\SearchPlugin');

    $this->setCacheBackend($cache_backend, $language_manager, 'search_plugins');
    // @todo Set an alter hook.
  }

}
