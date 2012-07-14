<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\HandlerPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugins\views\Discovery\ViewsDiscovery;

class HandlerPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views_plugins', 'handler');
    $this->factory = new DefaultFactory($this);
  }
}
