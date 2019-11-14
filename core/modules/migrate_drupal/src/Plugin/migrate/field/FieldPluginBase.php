<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\MigrateFieldInterface;

/**
 * The base class for all field plugins.
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate_drupal\Annotation\MigrateField
 * @see \Drupal\migrate_drupal\Plugin\MigrateFieldInterface
 * @see plugin_api
 *
 * @ingroup migration
 */
abstract class FieldPluginBase extends PluginBase implements MigrateFieldInterface {

  /**
   * Alters the migration for field definitions.
   *
   * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
   *   alterFieldMigration() instead.
   *
   * @see https://www.drupal.org/node/2944598
   * @see ::alterFieldMigration()
   */
  public function processField(MigrationInterface $migration) {
    @trigger_error('Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use alterFieldMigration() instead. See https://www.drupal.org/node/2944598.', E_USER_DEPRECATED);
    $this->alterFieldMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $process[0]['map'][$this->pluginId][$this->pluginId] = $this->pluginId;
    $migration->mergeProcessOfProperty('type', $process);
  }

  /**
   * Alert field instance migration.
   *
   * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
   *   alterFieldInstanceMigration() instead.
   *
   * @see https://www.drupal.org/node/2944598
   * @see ::alterFieldInstanceMigration()
   */
  public function processFieldInstance(MigrationInterface $migration) {
    @trigger_error('Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use alterFieldInstanceMigration() instead. See https://www.drupal.org/node/2944598.', E_USER_DEPRECATED);
    $this->alterFieldInstanceMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration) {
    // Nothing to do by default with field instances.
  }

  /**
   * Alter field widget migration.
   *
   * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
   *   alterFieldWidgetMigration() instead.
   *
   * @see https://www.drupal.org/node/2944598
   * @see ::alterFieldWidgetMigration()
   */
  public function processFieldWidget(MigrationInterface $migration) {
    @trigger_error('Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use alterFieldWidgetMigration() instead. See https://www.drupal.org/node/2944598.', E_USER_DEPRECATED);
    $this->alterFieldWidgetMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldWidgetMigration(MigrationInterface $migration) {
    $process = [];
    foreach ($this->getFieldWidgetMap() as $source_widget => $destination_widget) {
      $process['type']['map'][$source_widget] = $destination_widget;
    }
    $migration->mergeProcessOfProperty('options/type', $process);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterType(Row $row) {
    return $row->getSourceProperty('formatter/type');
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetType(Row $row) {
    return $row->getSourceProperty('widget/type');
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    // By default, use the plugin ID for the widget types.
    return [
      $this->pluginId => $this->pluginId . '_default',
    ];
  }

  /**
   * Alter field formatter migration.
   *
   * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
   *   alterFieldFormatterMigration() instead.
   *
   * @see https://www.drupal.org/node/2944598
   * @see ::processFieldFormatter()
   */
  public function processFieldFormatter(MigrationInterface $migration) {
    @trigger_error('Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use alterFieldFormatterMigration() instead. See https://www.drupal.org/node/2944598.', E_USER_DEPRECATED);
    $this->alterFieldFormatterMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldFormatterMigration(MigrationInterface $migration) {
    $process = [];
    // Some migrate field plugin IDs are prefixed with 'd6_' or 'd7_'. Since the
    // plugin ID is used in the static map as the module name, we have to remove
    // this prefix from the plugin ID.
    $plugin_id = preg_replace('/d[67]_/', '', $this->pluginId);
    foreach ($this->getFieldFormatterMap() as $source_format => $destination_format) {
      $process[0]['map'][$plugin_id][$source_format] = $destination_format;
    }
    $migration->mergeProcessOfProperty('options/type', $process);
  }

  /**
   * Defines the process pipeline for field values.
   *
   * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
   *   defineValueProcessPipeline() instead.
   *
   * @see https://www.drupal.org/node/2944598
   * @see ::defineValueProcessPipeline()
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    @trigger_error('Deprecated in Drupal 8.6.0, to be removed before Drupal 9.0.0. Use defineValueProcessPipeline() instead. See https://www.drupal.org/node/2944598.', E_USER_DEPRECATED);
    return $this->defineValueProcessPipeline($migration, $field_name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'get',
      'source' => $field_name,
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType(Row $row) {
    $field_type = $row->getSourceProperty('type');

    if (isset($this->pluginDefinition['type_map'][$field_type])) {
      return $this->pluginDefinition['type_map'][$field_type];
    }
    else {
      return $field_type;
    }
  }

}
