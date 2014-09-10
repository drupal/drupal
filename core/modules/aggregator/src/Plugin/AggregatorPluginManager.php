<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\AggregatorPluginManager.
 */

namespace Drupal\aggregator\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages aggregator plugins.
 *
 * @see \Drupal\aggregator\Annotation\AggregatorParser
 * @see \Drupal\aggregator\Annotation\AggregatorFetcher
 * @see \Drupal\aggregator\Annotation\AggregatorProcessor
 * @see \Drupal\aggregator\Plugin\AggregatorPluginSettingsBase
 * @see \Drupal\aggregator\Plugin\FetcherInterface
 * @see \Drupal\aggregator\Plugin\ProcessorInterface
 * @see \Drupal\aggregator\Plugin\ParserInterface
 * @see plugin_api
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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $type_annotations = array(
      'fetcher' => 'Drupal\aggregator\Annotation\AggregatorFetcher',
      'parser' => 'Drupal\aggregator\Annotation\AggregatorParser',
      'processor' => 'Drupal\aggregator\Annotation\AggregatorProcessor',
    );
    $plugin_interfaces = array(
      'fetcher' => 'Drupal\aggregator\Plugin\FetcherInterface',
      'parser' => 'Drupal\aggregator\Plugin\ParserInterface',
      'processor' => 'Drupal\aggregator\Plugin\ProcessorInterface',
    );

    parent::__construct("Plugin/aggregator/$type", $namespaces, $module_handler, $plugin_interfaces[$type], $type_annotations[$type]);
    $this->setCacheBackend($cache_backend, 'aggregator_' . $type . '_plugins');
  }

}
