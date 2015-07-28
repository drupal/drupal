<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Migrate\d6\MigrateSystemLoggingTest.
 */

namespace Drupal\system\Tests\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade error_level variable to system.logging.yml.
 *
 * @group system
 */
class MigrateSystemLoggingTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d6_system_logging');
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
