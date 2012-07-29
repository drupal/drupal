<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\DisplayExtenderPluginManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Views\Plugin\Discovery\ViewsDiscovery;

class DisplayExtenderPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views', 'display_extender');
    $this->factory = new DefaultFactory($this->discovery);
  }
}
