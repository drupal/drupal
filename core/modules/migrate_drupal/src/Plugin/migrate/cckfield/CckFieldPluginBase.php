<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\cckfield\MigrateCckFieldInterface.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\cckfield;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface;

/**
 * The base class for all cck field plugins.
 *
 * @see \Drupal\migrate_drupal\Plugin\MigratePluginManager
 * @see \Drupal\migrate_drupal\Annotation\MigrateCckField
 * @see \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface
 * @see plugin_api
 *
 * @ingroup migration
 */
abstract class CckFieldPluginBase extends PluginBase implements MigrateCckFieldInterface  {

  /**
   * {@inheritdoc}
   */
  public function processField(MigrationInterface $migration) {
    $process[0]['map'][$this->pluginId][$this->pluginId] = $this->pluginId;
    $migration->mergeProcessOfProperty('type', $process);
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldInstance(MigrationInterface $migration) {
    // Nothing to do by default with field instances.
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldWidget(MigrationInterface $migration) {
    $process = [];
    foreach ($this->getFieldWidgetMap() as $source_widget => $destination_widget) {
      $process['type']['map'][$source_widget] = $destination_widget;
    }
    $migration->mergeProcessOfProperty('options/type', $process);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    // By default use the plugin id for the widget types.
    return [
      $this->pluginId => $this->pluginId . '_default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldFormatter(MigrationInterface $migration) {
    $process = [];
    foreach ($this->getFieldFormatterMap() as $source_format => $destination_format) {
      $process[0]['map'][$this->pluginId][$source_format] = $destination_format;
    }
    $migration->mergeProcessOfProperty('options/type', $process);
  }

}
