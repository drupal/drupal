<?php

namespace Drupal\user\Plugin\migrate;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate_drupal\Plugin\migrate\FieldMigration;

/**
 * Plugin class for Drupal 7 user migrations dealing with fields and profiles.
 */
class User extends FieldMigration {

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;
      $definition['source'] = [
        'entity_type' => 'user',
        'ignore_map' => TRUE,
      ] + $this->source;
      $definition['destination']['plugin'] = 'null';
      if (\Drupal::moduleHandler()->moduleExists('field')) {
        $definition['source']['plugin'] = 'd7_field_instance';
        $field_migration = $this->migrationPluginManager->createStubMigration($definition);
        foreach ($field_migration->getSourcePlugin() as $row) {
          $field_name = $row->getSourceProperty('field_name');
          $field_type = $row->getSourceProperty('type');
          if (empty($field_type)) {
            continue;
          }
          if ($this->fieldPluginManager->hasDefinition($field_type)) {
            if (!isset($this->fieldPluginCache[$field_type])) {
              $this->fieldPluginCache[$field_type] = $this->fieldPluginManager->createInstance($field_type, [], $this);
            }
            $info = $row->getSource();
            $this->fieldPluginCache[$field_type]
              ->processFieldValues($this, $field_name, $info);
          }
          else {
            if ($this->cckPluginManager->hasDefinition($field_type)) {
              if (!isset($this->cckPluginCache[$field_type])) {
                $this->cckPluginCache[$field_type] = $this->cckPluginManager->createInstance($field_type, [], $this);
              }
              $info = $row->getSource();
              $this->cckPluginCache[$field_type]
                ->processCckFieldValues($this, $field_name, $info);
            }
            else {
              $this->process[$field_name] = $field_name;
            }
          }
        }
      }
      try {
        $definition['source']['plugin'] = 'profile_field';
        $profile_migration = $this->migrationPluginManager->createStubMigration($definition);
        // Ensure that Profile is enabled in the source DB.
        $profile_migration->checkRequirements();
        foreach ($profile_migration->getSourcePlugin() as $row) {
          $name = $row->getSourceProperty('name');
          $this->process[$name] = $name;
        }
      }
      catch (RequirementsException $e) {
        // The checkRequirements() call will fail when the profile module does
        // not exist on the source site.
      }
    }
    return parent::getProcess();
  }

}
