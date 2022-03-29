<?php

namespace Drupal\Tests\aggregator\Kernel\Migrate\d6;

use Drupal\Tests\SchemaCheckTestTrait;

/**
 * Upgrade variables to aggregator.settings.yml.
 *
 * @group aggregator
 * @group legacy
 */
class MigrateAggregatorConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigration('d6_aggregator_settings');
  }

  /**
   * Tests migration of aggregator variables to aggregator.settings.yml.
   */
  public function testAggregatorSettings() {
    $config = $this->config('aggregator.settings');
    $this->assertSame('aggregator', $config->get('fetcher'));
    $this->assertSame('aggregator', $config->get('parser'));
    $this->assertSame(['aggregator'], $config->get('processors'));
    $this->assertSame(600, $config->get('items.teaser_length'));
    $this->assertSame('<a> <b> <br /> <dd> <dl> <dt> <em> <i> <li> <ol> <p> <strong> <u> <ul>', $config->get('items.allowed_html'));
    $this->assertSame(9676800, $config->get('items.expire'));
    $this->assertSame(3, $config->get('source.list_max'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'aggregator.settings', $config->get());
  }

}
