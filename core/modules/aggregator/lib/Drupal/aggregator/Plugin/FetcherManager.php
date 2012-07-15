<?php

/**
 * @file
 * Definition of Drupal\aggregator\Plugin\FetcherManager.
 */

namespace Drupal\aggregator\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\HookDiscovery;
use Drupal\Component\Plugin\Factory\DefaultFactory;

/**
 * Manages aggregator fetcher plugins.
 */
class FetcherManager extends PluginManagerBase {

  public function __construct() {
    $this->discovery = new HookDiscovery('aggregator_fetch_info');
    $this->factory = new DefaultFactory($this->discovery);
  }
}
