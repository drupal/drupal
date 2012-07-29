<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\LocalizationPluginManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Views\Plugin\Discovery\ViewsDiscovery;

class LocalizationPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views', 'localization');
    $this->factory = new DefaultFactory($this);
  }
}
