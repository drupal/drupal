<?php

namespace Drupal\Tests\syslog\Kernel\Migrate\d6;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to syslog.settings.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateSyslogConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['syslog'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Enable syslog in the source database so that requirements are met.
    $this->sourceDatabase->update('system')
      ->condition('name', 'syslog')
      ->fields(['status' => '1'])
      ->execute();
    $this->executeMigration('d6_syslog_settings');
  }

  /**
   * Tests migration of syslog variables to syslog.settings.yml.
   */
  public function testSyslogSettings() {
    $config = $this->config('syslog.settings');
    $this->assertSame('drupal', $config->get('identity'));
    $this->assertSame(128, $config->get('facility'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'syslog.settings', $config->get());
  }

}
