<?php

namespace Drupal\taxonomy\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Drupal 6 term node migrations based on vocabularies.
 */
class D6TermNodeDeriver extends DeriverBase implements ContainerDeriverInterface {
  use MigrationDeriverTrait;

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * D6TermNodeDeriver constructor.
   *
   * @param string $base_plugin_id
   *   The base plugin ID this derivative is for.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   */
  public function __construct($base_plugin_id, PluginManagerInterface $migration_plugin_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition, $base_plugin_definitions = NULL) {
    try {
      foreach (static::getSourcePlugin('d6_taxonomy_vocabulary') as $row) {
        $source_vid = $row->getSourceProperty('vid');
        $definition = $base_plugin_definition;
        $definition['source']['vid'] = $source_vid;
        // migrate_drupal_migration_plugins_alter() adds to this definition.
        $this->derivatives[$source_vid] = $definition;
      }
    }
    catch (\Exception $e) {
      // It is possible no D6 tables are loaded so just eat exceptions.
    }
    return $this->derivatives;
  }

}
