<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\ArgumentPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugins\Discovery\ViewsDiscovery;

class ArgumentPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views_plugins', 'argument');
    $this->factory = new DefaultFactory($this);
  }
}
