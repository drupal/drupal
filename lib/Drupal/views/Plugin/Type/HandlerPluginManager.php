<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\HandlerPluginManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Views\Plugin\Discovery\ViewsDiscovery;

class HandlerPluginManager extends PluginManagerBase {
  /**
   * The handler type of this plugin manager, for example filter or field.
   *
   * @var string
   */
  protected $type;

  public function __construct($type) {
    $this->type = $type;

    $this->discovery = new ViewsDiscovery('views', $this->type);
    $this->factory = new DefaultFactory($this->discovery);
  }
}
