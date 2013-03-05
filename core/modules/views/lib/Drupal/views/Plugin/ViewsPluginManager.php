<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\ViewsPluginManager.
 */

namespace Drupal\views\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * Plugin type manager for all views plugins.
 */
class ViewsPluginManager extends PluginManagerBase {

  /**
   * Constructs a ViewsPluginManager object.
   *
   * @param string $type
   *   The plugin type, for example filter.
   * @param array $namespaces
   *   An array of paths keyed by it's corresponding namespaces.
   */
  public function __construct($type, array $namespaces = array()) {
    $this->discovery = new AnnotatedClassDiscovery('views', $type, $namespaces);
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);
    $this->discovery = new ProcessDecorator($this->discovery, array($this, 'processDefinition'));
    $this->discovery = new AlterDecorator($this->discovery, 'views_plugins_' . $type);
    $this->discovery = new CacheDecorator($this->discovery, 'views:' . $type, 'views_info');

    $this->factory = new DefaultFactory($this->discovery);

    $this->defaults += array(
      'parent' => 'parent',
      'plugin_type' => $type,
      'module' => 'views',
      'register_theme' => TRUE,
    );
  }

}
