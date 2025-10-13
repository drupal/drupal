<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Kernel\Migrate;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Upgrade variables to locale.settings.yml.
 */
#[Group('migrate_drupal_6')]
#[RunTestsInSeparateProcesses]
class MigrateLocaleConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['locale', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('locale_settings');
  }

  /**
   * Tests migration of locale variables to locale.settings.yml.
   */
  public function testLocaleSettings(): void {
    $config = $this->config('locale.settings');
    $this->assertTrue($config->get('cache_strings'));
    $this->assertSame('languages', $config->get('javascript.directory'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'locale.settings', $config->get());
  }

}
