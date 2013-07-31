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
use Drupal\Core\Plugin\Factory\ContainerFactory;

/**
 * Plugin type manager for all views plugins.
 */
class ViewsPluginManager extends PluginManagerBase {

  /**
   * Constructs a ViewsPluginManager object.
   *
   * @param string $type
   *   The plugin type, for example filter.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct($type, \Traversable $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery("Plugin/views/$type", $namespaces);
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);
    $this->discovery = new ProcessDecorator($this->discovery, array($this, 'processDefinition'));
    $this->discovery = new AlterDecorator($this->discovery, 'views_plugins_' . $type);
    $this->discovery = new CacheDecorator($this->discovery, 'views:' . $type, 'views_info');

    $this->factory = new ContainerFactory($this);

    $this->defaults += array(
      'parent' => 'parent',
      'plugin_type' => $type,
      'module' => 'views',
      'register_theme' => TRUE,
    );
  }

}
