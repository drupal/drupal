<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\ExposedFormPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;


class ExposedFormPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('views', 'exposed_form');
    $this->factory = new DefaultFactory($this);
  }
}
