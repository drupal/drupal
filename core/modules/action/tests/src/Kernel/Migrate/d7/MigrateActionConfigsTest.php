<?php

namespace Drupal\Tests\action\Kernel\Migrate\d7;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Upgrade variables to action.settings.yml.
 *
 * @group migrate_drupal_7
 */
class MigrateActionConfigsTest extends MigrateDrupal7TestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['action'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('action_settings');
  }

  /**
   * Tests migration of action variables to action.settings.yml.
   */
  public function testActionSettings() {
    $config = $this->config('action.settings');
    $this->assertSame(28, $config->get('recursion_limit'));
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'action.settings', $config->get());
  }

}
