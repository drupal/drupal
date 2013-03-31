<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\AggregatorPluginManager.
 */

namespace Drupal\aggregator\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
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
   * @param array $namespaces
   *   An array of paths keyed by it's corresponding namespaces.
   */
  public function __construct($type, array $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery('aggregator', $type, $namespaces);
    $this->discovery = new CacheDecorator($this->discovery, "aggregator_$type:" . language(LANGUAGE_TYPE_INTERFACE)->langcode);
    $this->factory = new DefaultFactory($this->discovery);
  }
}
