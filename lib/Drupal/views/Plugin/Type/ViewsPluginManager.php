<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\ViewsPluginManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\views\Plugin\Discovery\ViewsDiscovery;

class ViewsPluginManager extends PluginManagerBase {
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
