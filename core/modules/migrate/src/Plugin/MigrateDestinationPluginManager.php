<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\MigrateDestinationPluginManager.
 */

namespace Drupal\migrate\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate\Entity\MigrationInterface;

/**
 * Plugin manager for migrate destination plugins.
 *
 * @see \Drupal\migrate\Plugin\MigrateDestinationInterface
 * @see \Drupal\migrate\Plugin\destination\DestinationBase
 * @see \Drupal\migrate\Annotation\MigrateDestination
 * @see plugin_api
 *
 * @ingroup migration
 */
class MigrateDestinationPluginManager extends MigratePluginManager {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a MigrateDestinationPluginManager object.
   *
   * @param string $type
   *   The type of the plugin: row, source, process, destination, entity_field,
   *   id_map.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param string $annotation
   *   (optional) The annotation class name. Defaults to
   *   'Drupal\migrate\Annotation\MigrateDestination'.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityManagerInterface $entity_manager, $annotation = 'Drupal\migrate\Annotation\MigrateDestination') {
    parent::__construct($type, $namespaces, $cache_backend, $module_handler, $annotation);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   *
   * A specific createInstance method is necessary to pass the migration on.
   */
  public function createInstance($plugin_id, array $configuration = array(), MigrationInterface $migration = NULL) {
    if (substr($plugin_id, 0, 7) == 'entity:' && !$this->entityManager->getDefinition(substr($plugin_id, 7), FALSE)) {
      $plugin_id = 'null';
    }
    return parent::createInstance($plugin_id, $configuration, $migration);
  }

}
