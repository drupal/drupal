<?php

namespace Drupal\user\Plugin\migrate;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\Migration;

/**
 * Plugin class for user migrations dealing with profile values.
 */
class ProfileValues extends Migration {

  /**
   * Flag determining whether the process plugin has been initialized.
   *
   * @var bool
   */
  protected $init = FALSE;

  /**
   * {@inheritdoc}
   */
  public function getProcess() {
    if (!$this->init) {
      $this->init = TRUE;
      $definition['source'] = [
        'plugin' => 'profile_field',
        'ignore_map' => TRUE,
      ] + $this->source;
      $definition['destination']['plugin'] = 'null';
      $definition['idMap']['plugin'] = 'null';
      try {
        $profile_field_migration = $this->migrationPluginManager->createStubMigration($definition);
        $migrate_executable = new MigrateExecutable($profile_field_migration);
        $source_plugin = $profile_field_migration->getSourcePlugin();
        $source_plugin->checkRequirements();
        foreach ($source_plugin as $row) {
          $name = $row->getSourceProperty('name');
          $fid = $row->getSourceProperty('fid');
          // The user profile field name can be greater than 32 characters. Use
          // the migrated profile field name in the process pipeline.
          $configuration =
            [
              'migration' => 'user_profile_field',
              'source_ids' => $fid,
            ];
          $plugin = $this->processPluginManager->createInstance('migration_lookup', $configuration, $profile_field_migration);
          $new_value = $plugin->transform($fid, $migrate_executable, $row, 'tmp');
          if (isset($new_value[1])) {
            // Set the destination to the migrated profile field name.
            $this->process[$new_value[1]] = $name;
          }
          else {
            throw new MigrateSkipRowException("Can't migrate source field $name.");
          }
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
