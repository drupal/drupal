<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\Type\PluginManager.
 */

namespace Drupal\views\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

class PluginManager extends PluginManagerBase {

  /**
   * Constructs a PluginManager object.
   */
  public function __construct($type) {
    $this->discovery = new AnnotatedClassDiscovery('views', $type);
    $this->discovery = new AlterDecorator($this->discovery, 'views_plugins_' . $type);
    $this->discovery = new ProcessDecorator($this->discovery, array($this, 'processDefinition'));
    $this->discovery = new CacheDecorator($this->discovery, 'views:' . $type, 'views_info');

    $this->factory = new DefaultFactory($this);
    $this->defaults += array(
      'parent' => 'parent',
      'plugin_type' => $type,
      'module' => 'views',
      'register_theme' => TRUE,
    );
  }

}
