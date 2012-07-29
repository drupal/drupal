<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\QueryPluginManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Views\Plugin\Discovery\ViewsDiscovery;

class QueryPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views', 'query');
    $this->factory = new DefaultFactory($this);
  }
}
