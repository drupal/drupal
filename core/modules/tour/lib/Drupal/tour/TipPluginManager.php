<?php

/**
 * @file
 * Contains \Drupal\tour\TipPluginManager.
 */

namespace Drupal\tour;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;

/**
 * Configurable tour manager.
 */
class TipPluginManager extends PluginManagerBase {

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::__construct().
   *
   * @param array $namespaces
   *   An array of paths keyed by it's corresponding namespaces.
   */
  public function __construct(array $namespaces) {
    $this->discovery = new AnnotatedClassDiscovery('tour', 'tip', $namespaces);
    $this->discovery = new CacheDecorator($this->discovery, 'tour');
    $this->factory = new DefaultFactory($this->discovery);
  }

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::createInstance().
   *
   * Pass the TipsBag to the plugin constructor.
   */
  public function createInstance($plugin_id, array $configuration = array(), TipsBag $bag = NULL) {
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $this->discovery);
    return new $plugin_class($configuration, $plugin_id, $this->discovery, $bag);
  }
}
