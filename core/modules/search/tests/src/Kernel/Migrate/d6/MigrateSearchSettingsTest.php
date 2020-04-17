<?php

namespace Drupal\Tests\search\Kernel\Migrate\d6;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to search.settings.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateSearchSettingsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['search'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d6_search_settings');
  }

  /**
   * Tests migration of search variables to search.settings.yml.
   */
  public function testSearchSettings() {
    $config = $this->config('search.settings');
    $this->assertIdentical(3, $config->get('index.minimum_word_size'));
    $this->assertTrue($config->get('index.overlap_cjk'));
    $this->assertIdentical(100, $config->get('index.cron_limit'));
    $this->assertTrue($config->get('logging'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'search.settings', $config->get());
  }

}
