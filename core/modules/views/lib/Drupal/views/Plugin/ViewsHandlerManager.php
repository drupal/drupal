<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\ViewsHandlerManager.
 */

namespace Drupal\views\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\views\Plugin\Discovery\ViewsHandlerDiscovery;

/**
 * Plugin type manager for all views handlers.
 */
class ViewsHandlerManager extends PluginManagerBase {

  /**
   * Constructs a ViewsHandlerManager object.
   *
   * @param string $type
   *   The plugin type, for example filter.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct($type, \Traversable $namespaces) {
    $this->discovery = new ViewsHandlerDiscovery($type, $namespaces);
    $this->discovery = new CacheDecorator($this->discovery, "views:$type", 'views_info');

    $this->factory = new ContainerFactory($this);
  }

}
