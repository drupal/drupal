<?php

namespace Drupal\tour;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for tour items.
 *
 * @see \Drupal\tour\Annotation\Tip
 * @see \Drupal\tour\TipPluginBase
 * @see \Drupal\tour\TipPluginInterface
 * @see plugin_api
 */
class TipPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new TipPluginManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    // @todo In Drupal 10, it will not be necessary to accommodate tip plugins
    //   with different interface types. Change fourth param 'NULL' to
    //   `Drupal\tour\TourTipPluginInterface` in https://drupal.org/node/3195193.
    parent::__construct('Plugin/tour/tip', $namespaces, $module_handler, NULL, 'Drupal\tour\Annotation\Tip');

    $this->alterInfo('tour_tips_info');
    $this->setCacheBackend($cache_backend, 'tour_plugins');
  }

  /**
   * {@inheritdoc}
   *
   * @todo remove method in https://drupal.org/node/3195193.
   */
  protected function getFactory() {
    if (!$this->factory) {
      // TipContainerFactory is used instead of ContainerFactory to facilitate
      // tip plugins that use the deprecated TipPluginInterface abd the current
      // TourTipPluginInterface.
      $this->factory = new TipContainerFactory($this, $this->pluginInterface);
    }
    return $this->factory;
  }

}
