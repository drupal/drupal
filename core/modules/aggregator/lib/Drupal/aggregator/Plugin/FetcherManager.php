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

  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('aggregator', 'fetcher');
    $this->discovery = new CacheDecorator($this->discovery, 'aggregator_fetcher:' . language(LANGUAGE_TYPE_INTERFACE)->langcode);
    $this->factory = new DefaultFactory($this->discovery);
  }
}
