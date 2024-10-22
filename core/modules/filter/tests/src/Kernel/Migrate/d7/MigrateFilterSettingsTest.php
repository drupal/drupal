<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Filter's settings to configuration.
 *
 * @group filter
 */
class MigrateFilterSettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_filter_settings');
  }

  /**
   * Tests migration of Filter variables to configuration.
   */
  public function testFilterSettings(): void {
    $this->assertSame('plain_text', $this->config('filter.settings')->get('fallback_format'));
  }

}
