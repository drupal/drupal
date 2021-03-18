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
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $process[0]['map'][$this->pluginId][$this->pluginId] = $this->pluginId;
    $migration->mergeProcessOfProperty('type', $process);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration) {
    // Nothing to do by default with field instances.
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
    // Drupal 6 formatter settings migration has 'display_settings/format',
    // Drupal 7 formatter settings migration has 'formatter/type'.
    return $row->getSourceProperty('formatter/type') ?? $row->getSourceProperty('display_settings/format');
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
    // Drupal 6 widget settings migration has 'widget_type',
    // Drupal 7 widget settings migration has 'widget/type'.
    return $row->getSourceProperty('widget/type') ?? $row->getSourceProperty('widget_type');
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
   * {@inheritdoc}
   */
  public function alterFieldFormatterMigration(MigrationInterface $migration) {
    $process = [];
    // Certain migrate field plugins do not have type map annotation. For these,
    // the plugin ID is used for determining the source field type, which might
    // be prefixed with 'd6_' or 'd7_'. We have to remove this prefix from the
    // plugin ID.
    $plugin_id = preg_replace('/d[67]_/', '', $this->pluginId);
    $plugin_definition = $this->getPluginDefinition();
    $source_field_types = !empty($plugin_definition['type_map'])
      ? array_keys($plugin_definition['type_map'])
      : [$plugin_id];
    foreach ($source_field_types as $source_field_type) {
      foreach ($this->getFieldFormatterMap() as $source_format => $destination_format) {
        $process[0]['map'][$source_field_type][$source_format] = $destination_format;
      }
    }
    $migration->mergeProcessOfProperty('options/type', $process);
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
