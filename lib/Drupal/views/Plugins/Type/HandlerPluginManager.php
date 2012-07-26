<?php

/**
 * @file
 * Definition of Drupal\views\Plugins\Type\HandlerPluginManager.
 */

namespace Drupal\views\Plugins\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;

class HandlerPluginManager extends PluginManagerBase {
  /**
   * The handler type of this plugin manager, for example filter or field.
   *
   * @var string
   */
  protected $type;

  public function __construct($type) {
    $this->type = $type;

    if (in_array($this->type, array('sort', 'filter', 'relationship', 'field', 'area', 'argument'))) {
      $this->discovery = new AnnotatedClassDiscovery('views', $this->type);
    }
    $this->factory = new DefaultFactory($this->discovery);
  }
}
