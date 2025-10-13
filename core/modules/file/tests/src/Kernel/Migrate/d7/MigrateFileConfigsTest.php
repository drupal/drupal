<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Upgrade variables to file.settings.yml.
 */
#[Group('migrate_drupal_7')]
#[RunTestsInSeparateProcesses]
class MigrateFileConfigsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('file_settings');
  }

  /**
   * Tests migration of file variables to file.settings.yml.
   */
  public function testFileSettings(): void {
    $config = $this->config('file.settings');
    $this->assertSame('textfield', $config->get('description.type'));
    $this->assertSame(256, $config->get('description.length'));
    $this->assertSame('sites/default/files/icons', $config->get('icon.directory'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'file.settings', $config->get());
  }

}
