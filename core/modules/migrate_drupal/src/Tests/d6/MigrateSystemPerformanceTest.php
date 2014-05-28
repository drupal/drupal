<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemPerformanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests migration of system performance variables to configuration.
 */
class MigrateSystemPerformanceTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate performance variables to system.*.yml',
      'description'  => 'Upgrade performance variables to system.*.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_system_performance');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6SystemPerformance.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of system (Performance) variables to system.performance.yml.
   */
  public function testSystemPerformance() {
    $config = \Drupal::config('system.performance');
    $this->assertIdentical($config->get('css.preprocess'), FALSE);
    $this->assertIdentical($config->get('js.preprocess'), FALSE);
    $this->assertIdentical($config->get('cache.page.max_age'), 0);
  }

}
