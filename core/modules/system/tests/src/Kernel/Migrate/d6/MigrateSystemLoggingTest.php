<?php

namespace Drupal\Tests\system\Kernel\Migrate\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade error_level variable to system.logging.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateSystemLoggingTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('system_logging');
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
