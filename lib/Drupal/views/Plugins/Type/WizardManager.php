<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\WizardManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugins\Discovery\WizardDiscovery;

class WizardManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new WizardDiscovery('views_wizard');
    $this->factory = new DefaultFactory($this->discovery);
  }
}

