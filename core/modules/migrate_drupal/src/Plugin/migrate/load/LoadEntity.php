<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\load\LoadEntity.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\load;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\SourceEntityInterface;
use Drupal\migrate_drupal\Plugin\MigrateLoadInterface;
use Drupal\migrate_drupal\Plugin\CckFieldMigrateSourceInterface;

/**
 * Base class for entity load plugins.
 *
 * @ingroup migration
 *
 * @PluginID("drupal_entity")
 */
class LoadEntity extends PluginBase implements MigrateLoadInterface {

  /**
   * The list of bundles being loaded.
   *
   * @var array
   */
  protected $bundles;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->migration = $migration;
    $source_plugin = $this->migration->getSourcePlugin();
    if (!$source_plugin instanceof SourceEntityInterface) {
      throw new MigrateException('Migrations with a load plugin using LoadEntity should have an entity as source.');
    }
    if ($source_plugin->bundleMigrationRequired() && empty($configuration['bundle_migration'])) {
      throw new MigrateException(SafeMarkup::format('Source plugin @plugin requires the bundle_migration key to be set.', array('@plugin' => $source_plugin->getPluginId())));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function load(EntityStorageInterface $storage, $sub_id) {
    $entities = $this->loadMultiple($storage, array($sub_id));
    return isset($entities[$sub_id]) ? $entities[$sub_id] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(EntityStorageInterface $storage, array $sub_ids = NULL) {
    if (isset($this->configuration['bundle_migration'])) {
      /** @var \Drupal\migrate\Entity\MigrationInterface $bundle_migration */
      $bundle_migration = $storage->load($this->configuration['bundle_migration']);
      $source_id = array_keys($bundle_migration->getSourcePlugin()->getIds())[0];
      $this->bundles = array();
      foreach ($bundle_migration->getSourcePlugin()->getIterator() as $row) {
        $this->bundles[] = $row[$source_id];
      }
    }
    else {
      // This entity type has no bundles ('user', 'feed', etc).
      $this->bundles = array($this->migration->getSourcePlugin()->entityTypeId());
    }
    $sub_ids_to_load = isset($sub_ids) ? array_intersect($this->bundles, $sub_ids) : $this->bundles;
    $migrations = array();
    foreach ($sub_ids_to_load as $id) {
      $values = $this->migration->toArray();
      $values['id'] = $this->migration->id() . ':' . $id;
      $values['source']['bundle'] = $id;
      /** @var \Drupal\migrate_drupal\Entity\MigrationInterface $migration */
      $migration = $storage->create($values);
      try {
        $migration->getSourcePlugin()->checkRequirements();
        $source_plugin = $migration->getSourcePlugin();

        if ($source_plugin instanceof CckFieldMigrateSourceInterface) {
          foreach ($source_plugin->fieldData() as $field_name => $data) {
            $migration->setProcessOfProperty($field_name, $field_name);
          }
        }
        else {
          $fields = array_keys($migration->getSourcePlugin()->fields());
          $migration->setProcess($migration->getProcess() + array_combine($fields, $fields));
        }
        $migrations[$migration->id()] = $migration;
      }
      catch (RequirementsException $e) {

      }
    }

    return $migrations;
  }

}
