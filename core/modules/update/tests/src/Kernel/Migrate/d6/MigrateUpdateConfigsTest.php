<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Upgrade variables to update.settings.yml.
 */
#[Group('migrate_drupal_6')]
#[RunTestsInSeparateProcesses]
class MigrateUpdateConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('update_settings');
  }

  /**
   * Tests migration of update variables to update.settings.yml.
   */
  public function testUpdateSettings(): void {
    $config = $this->config('update.settings');
    $this->assertSame(2, $config->get('fetch.max_attempts'));
    $this->assertSame('https://updates.drupal.org/release-history', $config->get('fetch.url'));
    $this->assertSame('all', $config->get('notification.threshold'));
    $this->assertSame([], $config->get('notification.emails'));
    $this->assertSame(7, $config->get('check.interval_days'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'update.settings', $config->get());
  }

}
