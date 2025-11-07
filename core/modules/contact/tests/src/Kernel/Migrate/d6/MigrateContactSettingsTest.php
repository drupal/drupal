<?php

declare(strict_types=1);

namespace Drupal\Tests\contact\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Upgrade variables to contact.settings.yml.
 */
#[Group('migrate_drupal_6')]
#[RunTestsInSeparateProcesses]
class MigrateContactSettingsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['contact'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->executeMigrations(['contact_category', 'd6_contact_settings']);
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath():string {
    return __DIR__ . '/../../../../fixtures/drupal6.php';
  }

  /**
   * Tests migration of contact variables to contact.settings.yml.
   */
  public function testContactSettings(): void {
    $config = $this->config('contact.settings');
    $this->assertTrue($config->get('user_default_enabled'));
    $this->assertSame(3, $config->get('flood.limit'));
    $this->assertSame('some_other_category', $config->get('default_form'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'contact.settings', $config->get());
  }

}
