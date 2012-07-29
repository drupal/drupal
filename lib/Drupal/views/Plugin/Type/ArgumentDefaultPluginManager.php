<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\ArgumentDefaultPluginManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugin\Discovery\ViewsDiscovery;

class ArgumentDefaultPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views', 'argument_default');
    $this->factory = new DefaultFactory($this->discovery);
  }
}
