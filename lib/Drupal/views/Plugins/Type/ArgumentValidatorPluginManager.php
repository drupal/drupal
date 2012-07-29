<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\ArgumentValidatorPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugins\Discovery\ViewsDiscovery;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

class ArgumentValidatorPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('views', 'argument_validator');
    $this->factory = new DefaultFactory($this->discovery);
  }
}
