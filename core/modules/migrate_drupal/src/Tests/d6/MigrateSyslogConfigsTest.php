<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSyslogConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;

/**
 * Upgrade variables to syslog.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateSyslogConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('syslog');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadDumps(['Variable.php']);
    $this->executeMigration('d6_syslog_settings');
  }

  /**
   * Tests migration of syslog variables to syslog.settings.yml.
   */
  public function testSyslogSettings() {
    $config = $this->config('syslog.settings');
    $this->assertIdentical('drupal', $config->get('identity'));
    $this->assertIdentical('128', $config->get('facility'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'syslog.settings', $config->get());
  }

}
