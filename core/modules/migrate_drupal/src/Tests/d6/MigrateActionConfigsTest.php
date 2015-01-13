<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateActionConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade variables to action.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateActionConfigsTest extends MigrateDrupalTestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('action');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_action_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of action variables to action.settings.yml.
   */
  public function testActionSettings() {
    $config = $this->config('action.settings');
    $this->assertIdentical($config->get('recursion_limit'), 35);
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'action.settings', $config->get());
  }

}
