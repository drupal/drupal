<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateTextConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables from the Text module.
 */
class MigrateTextConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('text');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to text.settings.yml',
      'description'  => 'Upgrade variables to text.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

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
  }

}
