<?php

/**
 * @file
 * Contains \Drupal\statistics\Tests\Migrate\MigrateStatisticsConfigsTest.
 */

namespace Drupal\statistics\Tests\Migrate;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to statistics.settings.yml.
 *
 * @group statistics
 */
class MigrateStatisticsConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('statistics');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('statistics_settings');
  }

  /**
   * Tests migration of statistics variables to statistics.settings.yml.
   */
  public function testStatisticsSettings() {
    $config = $this->config('statistics.settings');
    $this->assertIdentical(FALSE, $config->get('access_log.enabled'));
    $this->assertIdentical(259200, $config->get('access_log.max_lifetime'));
    $this->assertIdentical(0, $config->get('count_content_views'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'statistics.settings', $config->get());
  }

}
