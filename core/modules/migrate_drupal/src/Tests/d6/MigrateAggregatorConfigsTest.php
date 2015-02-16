<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateAggregatorConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to aggregator.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateAggregatorConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('aggregator');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_aggregator_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of aggregator variables to aggregator.settings.yml.
   */
  public function testAggregatorSettings() {
    $config = $this->config('aggregator.settings');
    $this->assertIdentical($config->get('fetcher'), 'aggregator');
    $this->assertIdentical($config->get('parser'), 'aggregator');
    $this->assertIdentical($config->get('processors'), array('aggregator'));
    $this->assertIdentical($config->get('items.teaser_length'), 600);
    $this->assertIdentical($config->get('items.allowed_html'), '<a> <b> <br /> <dd> <dl> <dt> <em> <i> <li> <ol> <p> <strong> <u> <ul>');
    $this->assertIdentical($config->get('items.expire'), 9676800);
    $this->assertIdentical($config->get('source.list_max'), 3);
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'aggregator.settings', $config->get());
  }

}
