<?php

/**
 * @file
 * Contains \Drupal\migrate\MigraterPluginManager.
 */

namespace Drupal\migrate\Plugin;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\migrate\Entity\MigrationInterface;

/**
 * Manages migrate plugins.
 *
 * @see hook_migrate_info_alter()
 */
class MigratePluginManager extends DefaultPluginManager {

  /**
   * Constructs a MigratePluginManager object.
   *
   * @param string $type
   *   The type of the plugin: row, source, process, destination, entity_field,
   * id_map.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param string $annotation
   *   The annotation class name.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, $annotation = 'Drupal\Component\Annotation\PluginID') {
    parent::__construct("Plugin/migrate/$type", $namespaces, $module_handler, $annotation);
    $this->alterInfo('migrate_' . $type . '_info');
    $this->setCacheBackend($cache_backend, 'migrate_plugins_' . $type);
  }

  /**
   * {@inheritdoc}
   *
   * A specific createInstance method is necessary to pass the migration on.
   */
  public function createInstance($plugin_id, array $configuration = array(), MigrationInterface $migration = NULL) {
    $plugin_definition = $this->getDefinition($plugin_id);
    $plugin_class = DefaultFactory::getPluginClass($plugin_id, $plugin_definition);
    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, 'Drupal\Core\Plugin\ContainerFactoryPluginInterface')) {
      $plugin = $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition, $migration);
    }
    else {
      $plugin = new $plugin_class($configuration, $plugin_id, $plugin_definition, $migration);
    }
    return $plugin;
  }

}
