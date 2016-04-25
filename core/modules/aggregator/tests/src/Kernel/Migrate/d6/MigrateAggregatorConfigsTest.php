<?php

namespace Drupal\Tests\aggregator\Kernel\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to aggregator.settings.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateAggregatorConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_aggregator_settings');
  }

  /**
   * Tests migration of aggregator variables to aggregator.settings.yml.
   */
  public function testAggregatorSettings() {
    $config = $this->config('aggregator.settings');
    $this->assertIdentical('aggregator', $config->get('fetcher'));
    $this->assertIdentical('aggregator', $config->get('parser'));
    $this->assertIdentical(array('aggregator'), $config->get('processors'));
    $this->assertIdentical(600, $config->get('items.teaser_length'));
    $this->assertIdentical('<a> <b> <br /> <dd> <dl> <dt> <em> <i> <li> <ol> <p> <strong> <u> <ul>', $config->get('items.allowed_html'));
    $this->assertIdentical(9676800, $config->get('items.expire'));
    $this->assertIdentical(3, $config->get('source.list_max'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'aggregator.settings', $config->get());
  }

}
