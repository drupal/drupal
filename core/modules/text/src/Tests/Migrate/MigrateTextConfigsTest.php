<?php

/**
 * @file
 * Contains \Drupal\text\Tests\Migrate\MigrateTextConfigsTest.
 */

namespace Drupal\text\Tests\Migrate;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

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
  protected function setUp() {
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
