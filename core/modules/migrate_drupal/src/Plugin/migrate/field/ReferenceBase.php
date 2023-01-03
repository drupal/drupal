<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Base class for Drupal reference fields.
 */
abstract class ReferenceBase extends FieldPluginBase {

  /**
   * Gets the plugin ID for the reference type migration.
   *
   * The reference type migration will be added as a required dependency.
   *
   * @return string
   *   The plugin id.
   */
  abstract protected function getEntityTypeMigrationId();

  /**
   * Gets the name of the field property which holds the entity ID.
   *
   * @return string
   *   The entity id.
   */
  abstract protected function entityId();

  /**
   * {@inheritdoc}
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration) {
    parent::alterFieldInstanceMigration($migration);

    // Add the reference migration as a required dependency to this migration.
    $migration_dependencies = $migration->getMigrationDependencies(TRUE);
    array_push($migration_dependencies['required'], $this->getEntityTypeMigrationId());
    $migration_dependencies['required'] = array_unique($migration_dependencies['required']);
    $migration->set('migration_dependencies', $migration_dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => ['target_id' => $this->entityId()],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      $this->pluginId . '_select' => 'options_select',
      $this->pluginId . '_buttons' => 'options_buttons',
      $this->pluginId . '_autocomplete' => 'entity_reference_autocomplete_tags',
    ];
  }

}
