<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\CachePluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugins\views\Discovery\ViewsDiscovery;

class CachePluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views_plugins', 'cache');
    $this->factory = new DefaultFactory($this);
  }
}
