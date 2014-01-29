<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSimpletestConfigsTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of variables from the Simpletest module.
 */
class MigrateSimpletestConfigsTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simpletest');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate variables to simpletest.settings.yml',
      'description'  => 'Upgrade variables to simpletest.settings.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * Tests migration of simpletest variables to simpletest.settings.yml.
   */
  public function testSimpletestSettings() {
    $migration = entity_load('migration', 'd6_simpletest_settings');
    $dumps = array(
      drupal_get_path('module', 'migrate_drupal') . '/lib/Drupal/migrate_drupal/Tests/Dump/Drupal6SimpletestSettings.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
    $config = \Drupal::config('simpletest.settings');
    $this->assertIdentical($config->get('clear_results'), TRUE);
    $this->assertIdentical($config->get('httpauth.method'), CURLAUTH_BASIC);
    $this->assertIdentical($config->get('httpauth.password'), NULL);
    $this->assertIdentical($config->get('httpauth.username'), NULL);
    $this->assertIdentical($config->get('verbose'), TRUE);
  }
}
