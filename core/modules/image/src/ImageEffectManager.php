<?php

/**
 * @file
 * Contains \Drupal\image\ImageEffectManager.
 */

namespace Drupal\image;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages image effect plugins.
 */
class ImageEffectManager extends DefaultPluginManager {

  /**
   * Constructs a new ImageEffectManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ImageEffect', $namespaces, $module_handler, 'Drupal\image\Annotation\ImageEffect');

    $this->alterInfo('image_effect_info');
    $this->setCacheBackend($cache_backend, 'image_effect_plugins');
  }

}
