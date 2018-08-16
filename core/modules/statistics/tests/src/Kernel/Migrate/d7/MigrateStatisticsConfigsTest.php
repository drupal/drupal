<?php

namespace Drupal\Tests\statistics\Kernel\Migrate\d7;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Upgrade variables to statistics.settings.yml.
 *
 * @group migrate_drupal_7
 */
class MigrateStatisticsConfigsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['statistics'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('statistics_settings');
  }

  /**
   * Tests migration of statistics variables to statistics.settings.yml.
   */
  public function testStatisticsSettings() {
    $config = $this->config('statistics.settings');
    $this->assertIdentical(1, $config->get('count_content_views'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'statistics.settings', $config->get());
  }

}
