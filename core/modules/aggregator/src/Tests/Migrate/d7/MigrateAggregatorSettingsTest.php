<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\Migrate\d7\MigrateAggregatorSettingsTest.
 */

namespace Drupal\aggregator\Tests\Migrate\d7;

use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of Aggregator's variables to configuration.
 *
 * @group aggregator
 */
class MigrateAggregatorSettingsTest extends MigrateDrupal7TestBase {

  public static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_aggregator_settings');
  }

  /**
   * Tests migration of Aggregator variables to configuration.
   */
  public function testMigration() {
    $config = \Drupal::config('aggregator.settings')->get();
    $this->assertIdentical('aggregator', $config['fetcher']);
    $this->assertIdentical('aggregator', $config['parser']);
    $this->assertIdentical(['aggregator'], $config['processors']);
    $this->assertIdentical('<p> <div> <a>', $config['items']['allowed_html']);
    $this->assertIdentical(500, $config['items']['teaser_length']);
    $this->assertIdentical(86400, $config['items']['expire']);
    $this->assertIdentical(6, $config['source']['list_max']);
  }

}
