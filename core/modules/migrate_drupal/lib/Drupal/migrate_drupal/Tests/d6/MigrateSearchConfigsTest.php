<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSearchConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables for the Search module.
 */
class MigrateSearchConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('search');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to search.settings.yml',
      'description'  => 'Upgrade variables to search.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_search_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6SearchSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of search variables to search.settings.yml.
   */
  public function testSearchSettings() {
    $config = \Drupal::config('search.settings');
    $this->assertIdentical($config->get('index.minimum_word_size'), 3);
    $this->assertIdentical($config->get('index.overlap_cjk'), TRUE);
    $this->assertIdentical($config->get('index.cron_limit'), 100);
  }
}
