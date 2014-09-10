<?php

/**
 * @file
 * Contains \Drupal\tour\TipPluginManager.
 */

namespace Drupal\tour;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for tour items.
 *
 * @see \Drupal\tour\Annotation\Tip
 * @see \Drupal\tour\TipPluginBase
 * @see \Drupal\tour\TipPluginInterface
 * @see plugin_api
 */
class TipPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new TipPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/tour/tip', $namespaces, $module_handler, 'Drupal\tour\TipPluginInterface', 'Drupal\tour\Annotation\Tip');

    $this->alterInfo('tour_tips_info');
    $this->setCacheBackend($cache_backend, 'tour_plugins');
  }

}
