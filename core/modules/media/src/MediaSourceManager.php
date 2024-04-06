<?php

namespace Drupal\media;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\media\Attribute\MediaSource;

/**
 * Manages media source plugins.
 */
class MediaSourceManager extends DefaultPluginManager {

  /**
   * Constructs a new MediaSourceManager.
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
    parent::__construct('Plugin/media/Source', $namespaces, $module_handler, MediaSourceInterface::class, MediaSource::class, '\Drupal\media\Annotation\MediaSource');

    $this->alterInfo('media_source_info');
    $this->setCacheBackend($cache_backend, 'media_source_plugins');
  }

}
