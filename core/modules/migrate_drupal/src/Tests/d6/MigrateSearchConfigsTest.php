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
    $this->assertIdentical(3, $config->get('index.minimum_word_size'));
    $this->assertIdentical(TRUE, $config->get('index.overlap_cjk'));
    $this->assertIdentical(100, $config->get('index.cron_limit'));
    $this->assertIdentical(TRUE, $config->get('logging'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'search.settings', $config->get());
  }

}
