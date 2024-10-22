<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Image variables to configuration.
 *
 * @group image
 */
class MigrateImageSettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d7_image_settings');
  }

  /**
   * Tests the migration.
   */
  public function testMigration(): void {
    $config = $this->config('image.settings');
    // These settings are not recommended...
    $this->assertTrue($config->get('allow_insecure_derivatives'));
    $this->assertTrue($config->get('suppress_itok_output'));
    $this->assertSame("core/misc/druplicon.png", $config->get('preview_image'));
  }

}
