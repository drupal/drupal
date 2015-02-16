<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSearchConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to search.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateSearchConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('search');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_search_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of search variables to search.settings.yml.
   */
  public function testSearchSettings() {
    $config = $this->config('search.settings');
    $this->assertIdentical($config->get('index.minimum_word_size'), 3);
    $this->assertIdentical($config->get('index.overlap_cjk'), TRUE);
    $this->assertIdentical($config->get('index.cron_limit'), 100);
    $this->assertIdentical($config->get('logging'), TRUE);
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'search.settings', $config->get());
  }

}
