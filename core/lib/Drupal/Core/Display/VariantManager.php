<?php

namespace Drupal\Core\Display;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery of display variant plugins.
 *
 * @see \Drupal\Core\Display\Annotation\DisplayVariant
 * @see \Drupal\Core\Display\VariantInterface
 * @see \Drupal\Core\Display\VariantBase
 * @see plugin_api
 */
class VariantManager extends DefaultPluginManager {

  /**
   * Constructs a new VariantManager.
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
    parent::__construct('Plugin/DisplayVariant', $namespaces, $module_handler, 'Drupal\Core\Display\VariantInterface', 'Drupal\Core\Display\Annotation\DisplayVariant');

    $this->setCacheBackend($cache_backend, 'variant_plugins');
    $this->alterInfo('display_variant_plugin');
  }

}
