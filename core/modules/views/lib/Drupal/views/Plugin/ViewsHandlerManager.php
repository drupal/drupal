<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\ViewsHandlerManager.
 */

namespace Drupal\views\Plugin;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
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
   * @param array $namespaces
   *   (optional) An array of paths keyed by it's corresponding namespaces.
   */
  public function __construct($type, array $namespaces = array()) {
    $this->discovery = new ViewsHandlerDiscovery($type, $namespaces);
    $this->discovery = new CacheDecorator($this->discovery, "views:$type", 'views_info');

    $this->factory = new DefaultFactory($this->discovery);
  }

}
