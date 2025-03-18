<?php

namespace Drupal\views\Plugin;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\PreWarmablePluginManagerTrait;
use Drupal\Core\PreWarm\PreWarmableInterface;
use Drupal\views\Plugin\views\ViewsPluginInterface;
use Symfony\Component\DependencyInjection\Container;

/**
 * Plugin type manager for all views plugins.
 *
 * @ingroup views_plugins
 */
class ViewsPluginManager extends DefaultPluginManager implements PreWarmableInterface {

  use PreWarmablePluginManagerTrait;

  /**
   * Constructs a ViewsPluginManager object.
   *
   * @param string $type
   *   The plugin type, for example filter.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $plugin_definition_annotation_name = 'Drupal\views\Annotation\Views' . Container::camelize($type);
    // Special handling until all views plugins have attribute classes.
    $attribute_name_candidate = 'Drupal\views\Attribute\Views' . Container::camelize($type);
    $plugin_definition_attribute_name = class_exists($attribute_name_candidate) ? $attribute_name_candidate : Plugin::class;
    parent::__construct("Plugin/views/$type", $namespaces, $module_handler, ViewsPluginInterface::class, $plugin_definition_attribute_name, $plugin_definition_annotation_name);

    $this->defaults += [
      'parent' => 'parent',
      'plugin_type' => $type,
      'register_theme' => TRUE,
    ];

    $this->alterInfo('views_plugins_' . $type);
    $this->setCacheBackend($cache_backend, "views:$type");
  }

}
