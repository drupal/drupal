<?php

/**
 * @file
 * Definition of Drupal\aggregator\Plugin\FetcherManager.
 */

namespace Drupal\aggregator\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * Manages aggregator fetcher plugins.
 */
class FetcherManager extends PluginManagerBase {

  /**
   * Constructs a FetcherManager object.
   *
   * @param array $namespaces
   *   An array of paths keyed by it's corresponding namespaces.
   */
  public function __construct(array $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery('aggregator', 'fetcher', $namespaces);
    $this->discovery = new CacheDecorator($this->discovery, 'aggregator_fetcher:' . language(LANGUAGE_TYPE_INTERFACE)->langcode);
    $this->factory = new DefaultFactory($this->discovery);
  }
}
