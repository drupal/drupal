<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\ArgumentDefaultPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugins\Discovery\ViewsDiscovery;

class ArgumentDefaultPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views_plugins', 'argument default');
    $this->factory = new DefaultFactory($this);
  }
}
