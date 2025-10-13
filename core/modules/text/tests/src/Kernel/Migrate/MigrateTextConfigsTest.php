<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Kernel\Migrate;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Upgrade variables to text.settings.yml.
 */
#[Group('migrate_drupal_6')]
#[RunTestsInSeparateProcesses]
class MigrateTextConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('text_settings');
  }

  /**
   * Tests migration of text variables to text.settings.yml.
   */
  public function testTextSettings(): void {
    $config = $this->config('text.settings');
    $this->assertSame(456, $config->get('default_summary_length'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'text.settings', $config->get());
  }

}
