<?php

namespace Drupal\image;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\image\Annotation\ImageProcessPipeline;

/**
 * Service to manage ImageProcessPipeline plugins.
 */
class ImageProcessor extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/ImageProcessPipeline',
      $namespaces,
      $module_handler,
      ImageProcessPipelineInterface::class,
      ImageProcessPipeline::class
    );
    $this->alterInfo('image_process_pipeline_plugin_info');
    $this->setCacheBackend($cache_backend, 'image_process_pipeline_plugins');
  }

}
