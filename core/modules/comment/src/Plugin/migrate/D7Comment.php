<?php

namespace Drupal\comment\Plugin\migrate;

use Drupal\migrate_drupal\Plugin\migrate\FieldMigration;

/**
 * Migration plugin for Drupal 7 comments with fields.
 */
class D7Comment extends FieldMigration {

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if ($this->init) {
      return parent::getProcess();
    }
    $this->init = TRUE;
    if (!\Drupal::moduleHandler()->moduleExists('field')) {
      return parent::getProcess();
    }
    $definition['source'] = [
      'ignore_map' => TRUE,
    ] + $this->getSourceConfiguration();
    $definition['source']['plugin'] = 'd7_field_instance';
    $definition['destination']['plugin'] = 'null';
    $definition['idMap']['plugin'] = 'null';
    $field_migration = $this->migrationPluginManager->createStubMigration($definition);
    foreach ($field_migration->getSourcePlugin() as $row) {
      $field_name = $row->getSourceProperty('field_name');
      $field_type = $row->getSourceProperty('type');
      if ($this->fieldPluginManager->hasDefinition($field_type)) {
        if (!isset($this->fieldPluginCache[$field_type])) {
          $this->fieldPluginCache[$field_type] = $this->fieldPluginManager->createInstance($field_type, [], $this);
        }
        $info = $row->getSource();
        $this->fieldPluginCache[$field_type]->defineValueProcessPipeline($this, $field_name, $info);
      }
      else {
        $this->setProcessOfProperty($field_name, $field_name);
      }
    }
    return parent::getProcess();
  }

}
