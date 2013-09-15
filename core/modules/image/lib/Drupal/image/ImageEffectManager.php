<?php

/**
 * @file
 * Contains \Drupal\image\ImageEffectManager.
 */

namespace Drupal\image;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages image effect plugins.
 */
class ImageEffectManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ImageEffect', $namespaces, 'Drupal\image\Annotation\ImageEffect');

    $this->alterInfo($module_handler, 'image_effect_info');
    $this->setCacheBackend($cache_backend, $language_manager, 'image_effect');
  }

}
