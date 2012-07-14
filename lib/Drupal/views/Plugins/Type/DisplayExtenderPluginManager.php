<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\DisplayExtenderPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugins\views\Discovery\ViewsDiscovery;

class DisplayPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views_plugins', 'display_extender');
    $this->factory = new DefaultFactory($this);
  }
}
