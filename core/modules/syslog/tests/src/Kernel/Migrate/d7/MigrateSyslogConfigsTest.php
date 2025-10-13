<?php

declare(strict_types=1);

namespace Drupal\Tests\syslog\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Upgrade variables to syslog.settings.yml.
 */
#[Group('syslog')]
#[RunTestsInSeparateProcesses]
class MigrateSyslogConfigsTest extends MigrateDrupal7TestBase {

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
