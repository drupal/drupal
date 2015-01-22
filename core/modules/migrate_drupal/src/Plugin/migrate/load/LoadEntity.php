<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\load\LoadEntity.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\load;

use Drupal\Component\Utility\String;
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
      throw new MigrateException(String::format('Source plugin @plugin requires the bundle_migration key to be set.', array('@plugin' => $source_plugin->getPluginId())));
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
            switch ($data['type']) {
              case 'link':
                $this->processLinkField($field_name, $data, $migration);
                break;
              case 'filefield':
                $this->processFileField($field_name, $data, $migration);
                break;
              case 'text':
                $this->processTextField($field_name, $data, $migration);
                break;
              default:
                $process = $migration->getProcess();
                $process[$field_name] = $field_name;
                $migration->setProcess($process);
            }
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

  /**
   * Manipulate text fields with any per field type processing.
   *
   * @param string $field_name
   *   The field we're processing.
   * @param array $field_data
   *   The an array of field type data from the source.
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   */
  protected function processTextField($field_name, $field_data, MigrationInterface $migration) {
    // The data is stored differently depending on whether we're using
    // db storage.
    $value_key = $field_data['db_storage'] ? $field_name : "$field_name/value";
    $format_key = $field_data['db_storage'] ? $field_name . '_format' : "$field_name/format" ;

    $process = $migration->getProcess();

    $process["$field_name/value"] = $value_key;
    // See d6_user, signature_format for an example of the YAML that
    // represents this process array.
    $process["$field_name/format"] = [
      [
        'plugin' => 'static_map',
        'bypass' => TRUE,
        'source' => $format_key,
        'map' => [0 => NULL]
      ],
      ['plugin' => 'skip_process_on_empty'],
      [
        'plugin' => 'migration',
        'migration' => 'd6_filter_format',
        'source' => $format_key,
        'no_stub' => 1,
      ],
    ];

    $migration->setProcess($process);
  }

  /**
   * Manipulate file fields with any per field type processing.
   *
   * @param string $field_name
   *   The field we're processing.
   * @param array $field_data
   *   The an array of field type data from the source.
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   */
  protected function processFileField($field_name, $field_data, MigrationInterface $migration) {
    $process = $migration->getProcess();
    $process[$field_name] = [
      'plugin' => 'd6_cck_file',
      'source' => [
        $field_name,
        $field_name . '_list',
        $field_name . '_data',
      ],
    ];
    $migration->setProcess($process);
  }

  /**
   * Manipulate link fields with any per field type processing.
   *
   * @param string $field_name
   *   The field we're processing.
   * @param array $field_data
   *   The an array of field type data from the source.
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   */
  protected function processLinkField($field_name, $field_data, MigrationInterface $migration) {
    // Specifically process the link field until core is fixed.
    // @see https://www.drupal.org/node/2235457
    $process = $migration->getProcess();
    $process[$field_name] = [
      'plugin' => 'd6_cck_link',
      'source' => [
        $field_name,
        $field_name . '_title',
        $field_name . '_attributes',
      ],
    ];
    $migration->setProcess($process);
  }

}
