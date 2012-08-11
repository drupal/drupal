<?php

/**
 * @file
 * Definition of Drupal\aggregator\Plugin\FetcherManager.
 */

namespace Drupal\aggregator\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

/**
 * Manages aggregator fetcher plugins.
 */
class FetcherManager extends PluginManagerBase {

  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('aggregator', 'fetcher');
    $this->factory = new DefaultFactory($this->discovery);
  }
}
