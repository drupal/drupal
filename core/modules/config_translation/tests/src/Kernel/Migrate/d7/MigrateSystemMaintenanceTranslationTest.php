<?php

namespace Drupal\Tests\config_translation\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migrations of i18n maintenance variable.
 *
 * @group migrate_drupal_7
 */
class MigrateSystemMaintenanceTranslationTest extends MigrateDrupal7TestBase {

  public static $modules = [
    'language',
    'config_translation',
    // Required for translation migrations.
    'migrate_drupal_multilingual',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d7_system_maintenance_translation');
  }

  /**
   * Tests migrations of i18n maintenance variable.
   */
  public function testSystemMaintenance() {
    $config = \Drupal::service('language_manager')->getLanguageConfigOverride('is', 'system.maintenance');
    $this->assertSame('is - This is a custom maintenance mode message.', $config->get('message'));
  }

}
