<?php

declare(strict_types=1);

namespace Drupal\Tests\config_translation\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade i18n maintenance variables to system.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateSystemMaintenanceTranslationTest extends MigrateDrupal6TestBase {

  protected static $modules = [
    'language',
    'config_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations([
      'language',
      'system_maintenance',
      'd6_system_maintenance_translation',
    ]);
  }

  /**
   * Tests migration of system variables to system.maintenance.yml.
   */
  public function testSystemMaintenance(): void {
    $config = \Drupal::service('language_manager')->getLanguageConfigOverride('fr', 'system.maintenance');
    $this->assertSame('fr - Drupal is currently under maintenance. We should be back shortly. Thank you for your patience.', $config->get('message'));
  }

}
