<?php

declare(strict_types=1);

namespace Drupal\Tests\syslog\Kernel\Migrate\d7;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Upgrade variables to syslog.settings.yml.
 *
 * @group syslog
 */
class MigrateSyslogConfigsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['syslog'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(static::$modules);
    $this->executeMigration('d7_syslog_settings');
  }

  /**
   * Tests migration of syslog variables to syslog.settings.yml.
   */
  public function testSyslogSettings(): void {
    $config = $this->config('syslog.settings');
    // 8 == LOG_USER
    $this->assertSame(8, $config->get('facility'));
    $this->assertSame('!base_url|!timestamp|!type|!ip|!request_uri|!referer|!uid|!link|!message', $config->get('format'));
    $this->assertSame('drupal', $config->get('identity'));
  }

}
