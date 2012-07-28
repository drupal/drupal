<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\DisplayExtenderPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

class DisplayExtenderPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('views', 'display_extender');
    $this->factory = new DefaultFactory($this);
  }
}
