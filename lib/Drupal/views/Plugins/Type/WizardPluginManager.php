<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\WizardPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

class WizardPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('views', 'wizard');
    $this->factory = new DefaultFactory($this->discovery);
  }
}

