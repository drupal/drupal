<?php

namespace Drupal\image;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\image\Attribute\ImageEffect;

/**
 * Manages image effect plugins.
 *
 * @see hook_image_effect_info_alter()
 * @see \Drupal\image\Annotation\ImageEffect
 * @see \Drupal\image\ConfigurableImageEffectInterface
 * @see \Drupal\image\ConfigurableImageEffectBase
 * @see \Drupal\image\ImageEffectInterface
 * @see \Drupal\image\ImageEffectBase
 * @see plugin_api
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
    parent::__construct('Plugin/ImageEffect', $namespaces, $module_handler, ImageEffectInterface::class, ImageEffect::class, 'Drupal\image\Annotation\ImageEffect');

    $this->alterInfo('image_effect_info');
    $this->setCacheBackend($cache_backend, 'image_effect_plugins');
  }

}
