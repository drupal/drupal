<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\CachePluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugins\Discovery\ViewsDiscovery;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

class CachePluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('views', 'cache');
    $this->factory = new DefaultFactory($this->discovery);
  }
}
