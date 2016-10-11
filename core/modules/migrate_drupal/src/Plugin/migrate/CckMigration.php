<?php

namespace Drupal\migrate_drupal\Plugin\migrate;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrateDestinationPluginManager;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migration plugin class for migrations dealing with CCK field values.
 */
class CckMigration extends Migration implements ContainerFactoryPluginInterface {

  /**
   * Flag indicating whether the CCK data has been filled already.
   *
   * @var bool
   */
  protected $init = FALSE;

  /**
   * List of cckfield plugin IDs which have already run.
   *
   * @var string[]
   */
  protected $processedFieldTypes = [];

  /**
   * Already-instantiated cckfield plugins, keyed by ID.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface[]
   */
  protected $cckPluginCache;

  /**
   * The cckfield plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface
   */
  protected $cckPluginManager;

  /**
   * Constructs a CckMigration.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface $cck_manager
   *   The cckfield plugin manager.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $source_plugin_manager
   *   The source migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $process_plugin_manager
   *   The process migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigrateDestinationPluginManager $destination_plugin_manager
   *   The destination migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $idmap_plugin_manager
   *   The ID map migration plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateCckFieldPluginManagerInterface $cck_manager, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManagerInterface $source_plugin_manager, MigratePluginManagerInterface $process_plugin_manager, MigrateDestinationPluginManager $destination_plugin_manager, MigratePluginManagerInterface $idmap_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration_plugin_manager, $source_plugin_manager, $process_plugin_manager, $destination_plugin_manager, $idmap_plugin_manager);
    $this->cckPluginManager = $cck_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.migrate.cckfield'),
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.source'),
      $container->get('plugin.manager.migrate.process'),
      $container->get('plugin.manager.migrate.destination'),
      $container->get('plugin.manager.migrate.id_map')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;
      $source_plugin = $this->migrationPluginManager->createInstance($this->pluginId)->getSourcePlugin();
      if ($source_plugin instanceof RequirementsInterface) {
        try {
          $source_plugin->checkRequirements();
        }
        catch (RequirementsException $e) {
          // Kill the rest of the method.
          $source_plugin = [];
        }
      }
      foreach ($source_plugin as $row) {
        $field_type = $row->getSourceProperty('type');
        try {
          $plugin_id = $this->cckPluginManager->getPluginIdFromFieldType($field_type, [], $this);
        }
        catch (PluginNotFoundException $ex) {
          continue;
        }

        if (!isset($this->processedFieldTypes[$field_type])) {
          $this->processedFieldTypes[$field_type] = TRUE;
          // Allow the cckfield plugin to alter the migration as necessary so
          // that it knows how to handle fields of this type.
          if (!isset($this->cckPluginCache[$field_type])) {
            $this->cckPluginCache[$field_type] = $this->cckPluginManager->createInstance($plugin_id, [], $this);
          }
          call_user_func([$this->cckPluginCache[$field_type], $this->pluginDefinition['cck_plugin_method']], $this);
        }
      }
    }
    return parent::getProcess();
  }

}
