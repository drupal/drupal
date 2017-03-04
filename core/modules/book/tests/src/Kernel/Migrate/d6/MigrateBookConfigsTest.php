<?php

namespace Drupal\Tests\book\Kernel\Migrate\d6;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to book.settings.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateBookConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['book'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_book_settings');
  }

  /**
   * Tests migration of book variables to book.settings.yml.
   */
  public function testBookSettings() {
    $config = $this->config('book.settings');
    $this->assertIdentical('book', $config->get('child_type'));
    $this->assertIdentical('all pages', $config->get('block.navigation.mode'));
    $this->assertIdentical(['book'], $config->get('allowed_types'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'book.settings', $config->get());
  }

}
