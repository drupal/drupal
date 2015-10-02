<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\builder\d6\CckMigration.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\builder\d6;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\RequirementsInterface;
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

    $source_plugin = $migration->getSourcePlugin();
    // The source plugin will throw RequirementsException if CCK is not enabled,
    // in which case there is nothing else for us to do.
    if ($source_plugin instanceof RequirementsInterface) {
      try {
        $source_plugin->checkRequirements();
      }
      catch (RequirementsException $e) {
        return [$migration];
      }
    }

    // Loop through every field that will be migrated.
    foreach ($source_plugin as $field) {
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
