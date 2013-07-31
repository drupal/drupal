<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\AggregatorPluginManager.
 */

namespace Drupal\aggregator\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * Manages aggregator plugins.
 */
class AggregatorPluginManager extends PluginManagerBase {

  /**
   * Constructs a AggregatorPluginManager object.
   *
   * @param string $type
   *   The plugin type, for example fetcher.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct($type, \Traversable $namespaces) {
    $type_annotations = array(
      'fetcher' => 'Drupal\aggregator\Annotation\AggregatorFetcher',
      'parser' => 'Drupal\aggregator\Annotation\AggregatorParser',
      'processor' => 'Drupal\aggregator\Annotation\AggregatorProcessor',
    );

    $annotation_namespaces = array(
      'Drupal\aggregator\Annotation' => DRUPAL_ROOT . '/core/modules/aggregator/lib',
    );

    $this->discovery = new AnnotatedClassDiscovery("Plugin/aggregator/$type", $namespaces, $annotation_namespaces, $type_annotations[$type]);
    $this->discovery = new CacheDecorator($this->discovery, "aggregator_$type:" . language(Language::TYPE_INTERFACE)->id);
    $this->factory = new DefaultFactory($this->discovery);
  }
}
