<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\builder\d6\CckMigration.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\builder\d6;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate_drupal\Plugin\migrate\builder\CckBuilder;

/**
 * @PluginID("d6_cck_migration")
 */
class CckMigration extends CckBuilder {

  /**
   * List of cckfield plugin IDs which have already run.
   *
   * @var string[]
   */
  protected $processedFieldTypes = [];

  /**
   * {@inheritdoc}
   */
  public function buildMigrations(array $template) {
    $migration = Migration::create($template);

    // Loop through every field that will be migrated.
    foreach ($migration->getSourcePlugin() as $field) {
      $field_type = $field->getSourceProperty('type');

      // Each field type should only be processed once.
      if (in_array($field_type, $this->processedFieldTypes)) {
        continue;
      }
      // Only process the current field type if a relevant cckfield plugin
      // exists.
      elseif ($this->cckPluginManager->hasDefinition($field_type)) {
        $this->processedFieldTypes[] = $field_type;
        // Allow the cckfield plugin to alter the migration as necessary so that
        // it knows how to handle fields of this type.
        $this->cckPluginManager
          ->createInstance($field_type, [], $migration)
          ->{$this->configuration['cck_plugin_method']}($migration);
      }
    }

    return [$migration];
  }

}
