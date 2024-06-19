<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\Kernel\Migrate\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests rollback of user profile translations.
 *
 * @group migrate_drupal_6
 */
class MigrateUserProfileTranslationRollbackTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config_translation',
    'locale',
    'language',
    'field',
  ];

  /**
   * Tests rollback of the complete node migration.
   */
  public function testRollback(): void {
    $migration_ids = [
      'user_profile_field',
      'd6_profile_field_option_translation',
      'user_profile_field_instance',
      'd6_user_profile_field_instance_translation',
      'language',
    ];

    /** @var \Drupal\migrate\Plugin\MigrationPluginManager $migration_plugin_manager */
    $migration_plugin_manager = \Drupal::service('plugin.manager.migration');

    $migrations = [];
    foreach ($migration_ids as $migration_id) {
      $migrations[$migration_id] = $migration_plugin_manager->createInstance($migration_id, []);
    }
    $migrations = $migration_plugin_manager->buildDependencyMigration($migrations, []);

    // Execute the import.
    $ids = array_keys($migrations);
    $this->executeMigrations($ids);

    // Execute the rollback.
    $ids = array_reverse($ids);
    try {
      foreach ($ids as $id) {
        // Language rollback tries to rollback the default language so skip it.
        if ($id == 'language') {
          continue;
        }
        $migration = $migrations[$id];
        (new MigrateExecutable($migration, $this))->rollback();
      }
    }
    catch (\Exception $e) {
    }
  }

}
