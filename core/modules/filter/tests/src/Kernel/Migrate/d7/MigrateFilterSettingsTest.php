<?php

namespace Drupal\Tests\filter\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Filter's settings to configuration.
 *
 * @group filter
 */
class MigrateFilterSettingsTest extends MigrateDrupal7TestBase {

  public static $modules = ['filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_filter_settings');
  }

  /**
   * Tests migration of Filter variables to configuration.
   */
  public function testFilterSettings() {
    $this->assertSame('plain_text', $this->config('filter.settings')->get('fallback_format'));
  }

}
