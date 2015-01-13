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
 * Upgrade performance variables to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemPerformanceTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $migration = entity_load('migration', 'd6_system_performance');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();
  }

  /**
   * Tests migration of system (Performance) variables to system.performance.yml.
   */
  public function testSystemPerformance() {
    $config = $this->config('system.performance');
    $this->assertIdentical($config->get('css.preprocess'), FALSE);
    $this->assertIdentical($config->get('js.preprocess'), FALSE);
    $this->assertIdentical($config->get('cache.page.max_age'), 0);
    $this->assertIdentical($config->get('cache.page.use_internal'), TRUE);
    $this->assertIdentical($config->get('response.gzip'), TRUE);
  }

}
