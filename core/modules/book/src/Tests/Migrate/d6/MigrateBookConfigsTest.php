<?php

/**
 * @file
 * Contains \Drupal\book\Tests\Migrate\d6\MigrateBookConfigsTest.
 */

namespace Drupal\book\Tests\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to book.settings.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateBookConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('book', 'system', 'node', 'field', 'text', 'entity_reference');

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
    $this->assertIdentical(array('book'), $config->get('allowed_types'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'book.settings', $config->get());
  }

}
