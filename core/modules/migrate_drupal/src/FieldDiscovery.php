<?php

namespace Drupal\migrate_drupal;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides field discovery for Drupal 6 & 7 migrations.
 */
class FieldDiscovery implements FieldDiscoveryInterface {

  /**
   * The CCK plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface
   */
  protected $cckPluginManager;

  /**
   * An array of already discovered field plugin information.
   *
   * @var array
   */
  protected $fieldPluginCache;

  /**
   * The field plugin manager.
   *
   * @var \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface
   */
  protected $fieldPluginManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The logger channel service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * A cache of discovered fields.
   *
   * It is an array of arrays. If the entity type is bundleable, a third level
   * of arrays is added to account for fields discovered at the bundle level.
   *
   * [{core}][{entity_type}][{bundle}]
   *
   * @var array
   */
  protected $discoveredFieldsCache = [];

  /**
   * An array of bundle keys, keyed by drupal core version.
   *
   * In Drupal 6, only nodes were fieldable, and the bundles were called
   * 'type_name'.  In Drupal 7, everything became entities, and the more
   * generic 'bundle' was used.
   *
   * @var array
   */
  protected $bundleKeys = [
    FieldDiscoveryInterface::DRUPAL_6 => 'type_name',
    FieldDiscoveryInterface::DRUPAL_7 => 'bundle',
  ];

  /**
   * An array of source plugin ids, keyed by Drupal core version.
   *
   * @var array
   */
  protected $sourcePluginIds = [
    FieldDiscoveryInterface::DRUPAL_6 => 'd6_field_instance',
    FieldDiscoveryInterface::DRUPAL_7 => 'd7_field_instance',
  ];

  /**
   * An array of supported Drupal core versions.
   *
   * @var array
   */
  protected $supportedCoreVersions = [
    FieldDiscoveryInterface::DRUPAL_6,
    FieldDiscoveryInterface::DRUPAL_7,
  ];

  /**
   * Constructs a FieldDiscovery object.
   *
   * @param \Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface $field_plugin_manager
   *   The field plugin manager.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel service.
   */
  public function __construct(MigrateFieldPluginManagerInterface $field_plugin_manager, MigrationPluginManagerInterface $migration_plugin_manager, LoggerInterface $logger) {
    $this->fieldPluginManager = $field_plugin_manager;
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function addAllFieldProcesses(MigrationInterface $migration) {
    $core = $this->getCoreVersion($migration);
    $fields = $this->getAllFields($core);
    foreach ($fields as $entity_type_id => $bundle) {
      $this->addEntityFieldProcesses($migration, $entity_type_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addEntityFieldProcesses(MigrationInterface $migration, $entity_type_id) {
    $core = $this->getCoreVersion($migration);
    $fields = $this->getAllFields($core);
    if (!empty($fields[$entity_type_id])  && is_array($fields[$entity_type_id])) {
      foreach ($fields[$entity_type_id] as $bundle => $fields) {
        $this->addBundleFieldProcesses($migration, $entity_type_id, $bundle);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addBundleFieldProcesses(MigrationInterface $migration, $entity_type_id, $bundle) {
    $core = $this->getCoreVersion($migration);
    $fields = $this->getAllFields($core);
    $plugin_definition = $migration->getPluginDefinition();
    if (empty($fields[$entity_type_id][$bundle])) {
      return;
    }
    $bundle_fields = $fields[$entity_type_id][$bundle];
    foreach ($bundle_fields as $field_name => $field_info) {
      $plugin = $this->getFieldPlugin($field_info['type'], $migration);
      if ($plugin) {
        $method = isset($plugin_definition['field_plugin_method']) ? $plugin_definition['field_plugin_method'] : 'defineValueProcessPipeline';

        // @todo Remove the following 3 lines of code prior to Drupal 9.0.0.
        // https://www.drupal.org/node/3032317
        if ($plugin instanceof MigrateCckFieldInterface) {
          $method = isset($plugin_definition['cck_plugin_method']) ? $plugin_definition['cck_plugin_method'] : 'processCckFieldValues';
        }

        call_user_func_array([
          $plugin,
          $method,
        ], [
          $migration,
          $field_name,
          $field_info,
        ]);
      }
      else {
        // Default to a get process plugin if this is a value migration.
        if ((empty($plugin_definition['field_plugin_method']) || $plugin_definition['field_plugin_method'] === 'defineValueProcessPipeline') && (empty($plugin_definition['cck_plugin_method']) || $plugin_definition['cck_plugin_method'] === 'processCckFieldValues')) {
          $migration->setProcessOfProperty($field_name, $field_name);
        }
      }
    }
  }

  /**
   * Returns the appropriate field plugin for a given field type.
   *
   * @param string $field_type
   *   The field type.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to retrieve the plugin for.
   *
   * @return \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface|\Drupal\migrate_drupal\Plugin\MigrateFieldInterface|bool
   *   The appropriate field or cck plugin to process this field type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \InvalidArgumentException
   */
  protected function getFieldPlugin($field_type, MigrationInterface $migration) {
    $core = $this->getCoreVersion($migration);
    if (!isset($this->fieldPluginCache[$core][$field_type])) {
      try {
        $plugin_id = $this->fieldPluginManager->getPluginIdFromFieldType($field_type, ['core' => $core], $migration);
        $plugin = $this->fieldPluginManager->createInstance($plugin_id, ['core' => $core], $migration);
      }
      catch (PluginNotFoundException $ex) {
        // @todo Replace try/catch block with $plugin = FALSE for Drupal 9.
        // https://www.drupal.org/project/drupal/issues/3033733
        try {
          /** @var \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManager $cck_plugin_manager */
          $cck_plugin_manager = $this->getCckPluginManager();
          $plugin_id = $cck_plugin_manager->getPluginIdFromFieldType($field_type, ['core' => $core], $migration);
          $plugin = $cck_plugin_manager->createInstance($plugin_id, ['core' => $core], $migration);
        }
        catch (PluginNotFoundException $ex) {
          $plugin = FALSE;
        }
      }
      $this->fieldPluginCache[$core][$field_type] = $plugin;
    }
    return $this->fieldPluginCache[$core][$field_type];
  }

  /**
   * Gets all field information related to this migration.
   *
   * @param string $core
   *   The Drupal core version to get fields for.
   *
   * @return array
   *   A multidimensional array of source data from the relevant field instance
   *   migration, keyed first by entity type, then by bundle and finally by
   *   field name.
   */
  protected function getAllFields($core) {
    if (empty($this->discoveredFieldsCache[$core])) {
      $this->discoveredFieldsCache[$core] = [];
      $source_plugin = $this->getSourcePlugin($core);
      foreach ($source_plugin as $row) {
        /** @var \Drupal\migrate\Row $row */
        if ($core === FieldDiscoveryInterface::DRUPAL_7) {
          $entity_type_id = $row->get('entity_type');
        }
        else {
          $entity_type_id = 'node';
        }
        $bundle = $row->getSourceProperty($this->bundleKeys[$core]);
        $this->discoveredFieldsCache[$core][$entity_type_id][$bundle][$row->getSourceProperty('field_name')] = $row->getSource();
      }
    }
    return $this->discoveredFieldsCache[$core];
  }

  /**
   * Gets all field information for a particular entity type.
   *
   * @param string $core
   *   The Drupal core version.
   * @param string $entity_type_id
   *   The legacy entity type ID.
   *
   * @return array
   *   A multidimensional array of source data from the relevant field instance
   *   migration for the entity type, keyed first by bundle and then by field
   *   name.
   */
  protected function getEntityFields($core, $entity_type_id) {
    $fields = $this->getAllFields($core);
    if (!empty($fields[$entity_type_id])) {
      return $fields[$entity_type_id];
    }
    return [];
  }

  /**
   * Gets all field information for a particular entity type and bundle.
   *
   * @param string $core
   *   The Drupal core version.
   * @param string $entity_type_id
   *   The legacy entity type ID.
   * @param string $bundle
   *   The legacy bundle (or content_type).
   *
   * @return array
   *   An array of source data from the relevant field instance migration for
   *   the bundle, keyed by field name.
   */
  protected function getBundleFields($core, $entity_type_id, $bundle) {
    $fields = $this->getEntityFields($core, $entity_type_id);
    if (!empty($fields[$bundle])) {
      return $fields[$bundle];
    }
    return [];
  }

  /**
   * Gets the deprecated CCK Plugin Manager service as a BC shim.
   *
   * We don't inject this service directly because it is deprecated, and we
   * don't want to instantiate the plugin manager unless we have to, to avoid
   * triggering deprecation errors.
   *
   * @return \Drupal\migrate_drupal\Plugin\MigrateCckFieldPluginManagerInterface
   *   The CCK Plugin Manager.
   */
  protected function getCckPluginManager() {
    if (!$this->cckPluginManager) {
      $this->cckPluginManager = \Drupal::service('plugin.manager.migrate.cckfield');
    }
    return $this->cckPluginManager;
  }

  /**
   * Gets the source plugin to use to gather field information.
   *
   * @param string $core
   *   The Drupal core version.
   *
   * @return array|\Drupal\migrate\Plugin\MigrateSourceInterface
   *   The source plugin, or an empty array if none can be found that meets
   *   requirements.
   */
  protected function getSourcePlugin($core) {
    $definition = $this->getFieldInstanceStubMigrationDefinition($core);
    $source_plugin = $this->migrationPluginManager
      ->createStubMigration($definition)
      ->getSourcePlugin();
    if ($source_plugin instanceof RequirementsInterface) {
      try {
        $source_plugin->checkRequirements();
      }
      catch (RequirementsException $e) {
        // If checkRequirements() failed, the source database did not support
        // fields (i.e., CCK is not installed in D6 or Field is not installed in
        // D7). Therefore, $fields will be empty and below we'll return an empty
        // array. The migration will proceed without adding fields.
        $this->logger->notice('Field discovery failed for Drupal core version @core. Did this site have the CCK or Field module installed? Error: @message', [
          '@core' => $core,
          '@message' => $e->getMessage(),
        ]);
        return [];
      }
    }
    return $source_plugin;
  }

  /**
   * Provides the stub migration definition for a given Drupal core version.
   *
   * @param string $core
   *   The Drupal core version.
   *
   * @return array
   *   The stub migration definition.
   */
  protected function getFieldInstanceStubMigrationDefinition($core) {
    return [
      'destination' => ['plugin' => 'null'],
      'idMap' => ['plugin' => 'null'],
      'source' => [
        'ignore_map' => TRUE,
        'plugin' => $this->sourcePluginIds[$core],
      ],
    ];
  }

  /**
   * Finds the core version of a Drupal migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   *
   * @return string|bool
   *   A string representation of the Drupal version, or FALSE.
   *
   * @throws \InvalidArgumentException
   */
  protected function getCoreVersion(MigrationInterface $migration) {
    $tags = $migration->getMigrationTags();
    if (in_array('Drupal 7', $tags, TRUE)) {
      return FieldDiscoveryInterface::DRUPAL_7;
    }
    elseif (in_array('Drupal 6', $tags, TRUE)) {
      return FieldDiscoveryInterface::DRUPAL_6;
    }
    throw new \InvalidArgumentException("Drupal Core version not found for this migration");
  }

}
