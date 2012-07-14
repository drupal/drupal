<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\LocalizationPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugins\views\Discovery\ViewsDiscovery;

class LocalizationPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views_plugins', 'localization');
    $this->factory = new DefaultFactory($this);
  }
}
