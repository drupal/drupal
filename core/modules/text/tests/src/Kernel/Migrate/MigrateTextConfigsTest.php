<?php

namespace Drupal\Tests\text\Kernel\Migrate;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to text.settings.yml.
 *
 * @group migrate_drupal_6
 */
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
  public function testTextSettings() {
    $config = $this->config('text.settings');
    $this->assertIdentical(456, $config->get('default_summary_length'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'text.settings', $config->get());
  }

}
