<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\ViewsPluginManager.
 */

namespace Drupal\views\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\DependencyInjection\Container;

/**
 * Plugin type manager for all views plugins.
 *
 * @ingroup views_plugins
 */
class ViewsPluginManager extends DefaultPluginManager {

  /**
   * Constructs a ViewsPluginManager object.
   *
   * @param string $type
   *   The plugin type, for example filter.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $plugin_definition_annotation_name = 'Drupal\views\Annotation\Views' . Container::camelize($type);
    parent::__construct("Plugin/views/$type", $namespaces, $module_handler, 'Drupal\views\Plugin\views\ViewsPluginInterface', $plugin_definition_annotation_name);

    $this->defaults += array(
      'parent' => 'parent',
      'plugin_type' => $type,
      'register_theme' => TRUE,
    );

    $this->alterInfo('views_plugins_' . $type);
    $this->setCacheBackend($cache_backend, "views:$type", array('config:core.extension', 'extension:views'));
  }

}
