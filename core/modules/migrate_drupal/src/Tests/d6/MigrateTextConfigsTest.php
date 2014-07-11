<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateTextConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\config\Tests\SchemaCheckTestTrait;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade variables to text.settings.yml.
 *
 * @group migrate_drupal
 */
class MigrateTextConfigsTest extends MigrateDrupalTestBase {

  use SchemaCheckTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('text');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_text_settings');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6TextSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests migration of text variables to text.settings.yml.
   */
  public function testTextSettings() {
    $config = \Drupal::config('text.settings');
    $this->assertIdentical($config->get('default_summary_length'), 456);
    $this->assertConfigSchema(\Drupal::service('config.typed'), 'text.settings', $config->get());
  }

}
