<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\CachePluginManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugin\Discovery\ViewsDiscovery;

class CachePluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views', 'cache');
    $this->factory = new DefaultFactory($this->discovery);
  }
}
