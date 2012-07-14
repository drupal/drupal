<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\WizardManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\HookDiscovery;
use Drupal\Core\Plugin\MapClassLoader;

class WizardManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new HookDiscovery('views_wizard');
    $this->factory = new DefaultFactory($this->discovery);
  }
}

