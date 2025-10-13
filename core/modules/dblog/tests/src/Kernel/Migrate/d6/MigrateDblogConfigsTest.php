<?php

declare(strict_types=1);

namespace Drupal\Tests\dblog\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Upgrade variables to dblog.settings.yml.
 */
#[Group('migrate_drupal_6')]
#[RunTestsInSeparateProcesses]
class MigrateDblogConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dblog'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Enable dblog in the source database so that requirements are met.
    $this->sourceDatabase->update('system')
      ->condition('name', 'dblog')
      ->fields(['status' => '1'])
      ->execute();
    $this->executeMigration('d6_dblog_settings');
  }

  /**
   * Tests migration of dblog variables to dblog.settings.yml.
   */
  public function testDblogSettings(): void {
    $config = $this->config('dblog.settings');
    $this->assertSame(10000, $config->get('row_limit'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'dblog.settings', $config->get());
  }

}
