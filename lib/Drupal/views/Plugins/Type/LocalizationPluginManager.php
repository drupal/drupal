<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\LocalizationPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Views\Plugins\Discovery\ViewsDiscovery;

class LocalizationPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views', 'localization');
    $this->factory = new DefaultFactory($this);
  }
}
