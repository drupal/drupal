<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\PagerPluginManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Views\Plugin\Discovery\ViewsDiscovery;

class PagerPluginManager extends PluginManagerBase {
  public function __construct() {
    $this->discovery = new ViewsDiscovery('views', 'pager');
    $this->factory = new DefaultFactory($this);
  }
}
