<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateBookConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables from the Book module.
 */
class MigrateBookConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('book');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to book.settings.yml',
      'description'  => 'Upgrade variables to book.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * Tests migration of book variables to book.settings.yml.
   */
  public function testBookSettings() {
    $migration = entity_load('migration', 'd6_book_settings');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6BookSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('book.settings');
    $this->assertIdentical($config->get('child_type'), 'book');
    $this->assertIdentical($config->get('block.navigation.mode'), 'all pages');
    $this->assertIdentical($config->get('allowed_types'), array('book'));
  }
}
