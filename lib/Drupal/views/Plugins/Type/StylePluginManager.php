<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\StylePluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Views\Plugins\Discovery\ViewsDiscovery;


class StylePluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views', 'style');
    $this->factory = new DefaultFactory($this);
  }
}
