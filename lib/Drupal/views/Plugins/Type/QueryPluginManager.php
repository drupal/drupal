<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\QueryPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugins\Discovery\ViewsDiscovery;

class QueryPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views_plugins', 'query');
    $this->factory = new DefaultFactory($this);
  }
}
