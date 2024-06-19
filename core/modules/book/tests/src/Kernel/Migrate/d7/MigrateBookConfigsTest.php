<?php

declare(strict_types=1);

namespace Drupal\Tests\book\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\SchemaCheckTestTrait;

/**
 * Tests the migration of Book settings.
 *
 * @group book
 * @group legacy
 */
class MigrateBookConfigsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['book', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('book_settings');
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../../fixtures/drupal7.php';
  }

  /**
   * Tests migration of book variables to book.settings.yml.
   */
  public function testBookSettings(): void {
    $config = $this->config('book.settings');
    $this->assertSame('book', $config->get('child_type'));
    $this->assertSame('all pages', $config->get('block.navigation.mode'));
    $this->assertSame(['book'], $config->get('allowed_types'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'book.settings', $config->get());
  }

}
