<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemLoggingTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade error_level variable to system.logging.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemLoggingTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_system_logging');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of system error_level variables to system.logging.yml.
   */
  public function testSystemLogging() {
    $config = $this->config('system.logging');
    $this->assertIdentical('some', $config->get('error_level'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'system.logging', $config->get());
  }

}
