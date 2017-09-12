<?php

namespace Drupal\migrate_drupal\Plugin\migrate;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrateDestinationPluginManager;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migration plugin class for migrations dealing with field config and values.
 */
class FieldMigration extends Migration implements ContainerFactoryPluginInterface {

  /**
   * Defines which configuration option has the migration processing function.
   *
   * Default method is 'field_plugin_method'. For backwards compatibility,
   * this constant is overridden in the CckMigration class, in order to
   * fallback to the old 'cck_plugin_method'.
   *
   * @const string
   */
  const PLUGIN_METHOD = 'field_plugin_method';

  /**
   * Flag indicating whether the field data has been filled already.
   *
   * @var bool
   */
  protected $init = FALSE;

  /**
   * List of field plugin IDs which have already run.
   *
   * @var string[]
   */
  protected $processedFieldTypes = [];

  /**
   * Already-instantiated field plugins, keyed by ID.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldInterface[]
   */
  protected $fieldPluginCache;

  /**
   * The field plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface
   */
  protected $fieldPluginManager;

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
   * Constructs a FieldMigration.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface $cck_manager
   *   The cckfield plugin manager.
   * @param \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface $field_manager
   *   The field plugin manager.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManager $source_plugin_manager
   *   The source migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManager $process_plugin_manager
   *   The process migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigrateDestinationPluginManager $destination_plugin_manager
   *   The destination migration plugin manager.
   * @param \Drupal\migrate\Plugin\MigratePluginManager $idmap_plugin_manager
   *   The ID map migration plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrateCckFieldPluginManagerInterface $cck_manager, MigrateFieldPluginManagerInterface $field_manager, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManager $source_plugin_manager, MigratePluginManager $process_plugin_manager, MigrateDestinationPluginManager $destination_plugin_manager, MigratePluginManager $idmap_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration_plugin_manager, $source_plugin_manager, $process_plugin_manager, $destination_plugin_manager, $idmap_plugin_manager);
    $this->cckPluginManager = $cck_manager;
    $this->fieldPluginManager = $field_manager;
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
      $container->get('plugin.manager.migrate.field'),
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
          $plugin_id = $this->fieldPluginManager->getPluginIdFromFieldType($field_type, [], $this);
          $manager = $this->fieldPluginManager;
        }
        catch (PluginNotFoundException $ex) {
          try {
            $plugin_id = $this->cckPluginManager->getPluginIdFromFieldType($field_type, [], $this);
            $manager = $this->cckPluginManager;
          }
          catch (PluginNotFoundException $ex) {
            continue;
          }
        }

        if (!isset($this->processedFieldTypes[$field_type]) && $manager->hasDefinition($plugin_id)) {
          $this->processedFieldTypes[$field_type] = TRUE;
          // Allow the field plugin to alter the migration as necessary so that
          // it knows how to handle fields of this type.
          if (!isset($this->fieldPluginCache[$field_type])) {
            $this->fieldPluginCache[$field_type] = $manager->createInstance($plugin_id, [], $this);
          }
        }
        $method = $this->pluginDefinition[static::PLUGIN_METHOD];
        call_user_func([$this->fieldPluginCache[$field_type], $method], $this);
      }
    }
    return parent::getProcess();
  }

}
