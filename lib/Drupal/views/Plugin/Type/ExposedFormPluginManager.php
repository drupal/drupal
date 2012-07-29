<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\ExposedFormPluginManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Views\Plugin\Discovery\ViewsDiscovery;

class ExposedFormPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views', 'exposed_form');
    $this->factory = new DefaultFactory($this);
  }
}
