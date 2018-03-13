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
   * Data provider for testBookSettings().
   *
   * @return array
   *   The data for each test scenario.
   */
  public function providerBookSettings() {
    return [
      // d6_book_settings was renamed to book_settings, but use the old alias to
      // prove that it works.
      // @see book_migration_plugins_alter()
      ['d6_book_settings'],
      ['book_settings'],
    ];
  }

  /**
   * Tests migration of book variables to book.settings.yml.
   *
   * @dataProvider providerBookSettings
   */
  public function testBookSettings($migration_id) {
    $this->executeMigration($migration_id);

    $config = $this->config('book.settings');
    $this->assertIdentical('book', $config->get('child_type'));
    $this->assertSame('book pages', $config->get('block.navigation.mode'));
    $this->assertIdentical(['book'], $config->get('allowed_types'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'book.settings', $config->get());
  }

}
