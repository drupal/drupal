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
   * The theme handler
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * An associative array where the keys are the enabled modules and themes.
   *
   * @var array
   */
  protected $providers;

  /**
   * {@inheritdoc}
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
