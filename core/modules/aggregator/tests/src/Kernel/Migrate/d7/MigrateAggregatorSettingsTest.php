<?php

namespace Drupal\Tests\aggregator\Kernel\Migrate\d7;

/**
 * Tests migration of Aggregator's variables to configuration.
 *
 * @group aggregator
 * @group legacy
 */
class MigrateAggregatorSettingsTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_aggregator_settings');
  }

  /**
   * Tests migration of Aggregator variables to configuration.
   */
  public function testMigration() {
    $config = \Drupal::config('aggregator.settings')->get();
    $this->assertSame('aggregator', $config['fetcher']);
    $this->assertSame('aggregator', $config['parser']);
    $this->assertSame(['aggregator'], $config['processors']);
    $this->assertSame('<p> <div> <a>', $config['items']['allowed_html']);
    $this->assertSame(500, $config['items']['teaser_length']);
    $this->assertSame(86400, $config['items']['expire']);
    $this->assertSame(6, $config['source']['list_max']);
  }

}
