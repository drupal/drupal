<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\LocalizationPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;


class LocalizationPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('views', 'localization');
    $this->factory = new DefaultFactory($this);
  }
}
