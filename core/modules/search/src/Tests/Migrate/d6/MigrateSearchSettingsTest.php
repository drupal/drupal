<?php

/**
 * @file
 * Contains \Drupal\search\Tests\Migrate\d6\MigrateSearchSettingsTest.
 */

namespace Drupal\search\Tests\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to search.settings.yml.
 *
 * @group search
 */
class MigrateSearchSettingsTest extends MigrateDrupal6TestBase {

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
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d6_search_settings');
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
