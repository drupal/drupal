<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\AggregatorPluginManager.
 */

namespace Drupal\aggregator\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages aggregator plugins.
 */
class AggregatorPluginManager extends DefaultPluginManager {

  /**
   * Constructs a AggregatorPluginManager object.
   *
   * @param string $type
   *   The plugin type, for example fetcher.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    $type_annotations = array(
      'fetcher' => 'Drupal\aggregator\Annotation\AggregatorFetcher',
      'parser' => 'Drupal\aggregator\Annotation\AggregatorParser',
      'processor' => 'Drupal\aggregator\Annotation\AggregatorProcessor',
    );

    parent::__construct("Plugin/aggregator/$type", $namespaces, $module_handler, $type_annotations[$type]);
    $this->setCacheBackend($cache_backend, $language_manager, 'aggregator_' . $type . '_plugins');
  }

}
