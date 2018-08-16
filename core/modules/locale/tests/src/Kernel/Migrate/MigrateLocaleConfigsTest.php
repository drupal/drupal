<?php

namespace Drupal\Tests\locale\Kernel\Migrate;

use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade variables to locale.settings.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateLocaleConfigsTest extends MigrateDrupal6TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['locale', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('locale_settings');
  }

  /**
   * Tests migration of locale variables to locale.settings.yml.
   */
  public function testLocaleSettings() {
    $config = $this->config('locale.settings');
    $this->assertIdentical(TRUE, $config->get('cache_strings'));
    $this->assertIdentical('languages', $config->get('javascript.directory'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'locale.settings', $config->get());
  }

}
