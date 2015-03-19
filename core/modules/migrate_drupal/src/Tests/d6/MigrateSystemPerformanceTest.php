<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateSystemPerformanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade performance variables to system.*.yml.
 *
 * @group migrate_drupal
 */
class MigrateSystemPerformanceTest extends MigrateDrupal6TestBase {

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
    $this->assertIdentical(FALSE, $config->get('css.preprocess'));
    $this->assertIdentical(FALSE, $config->get('js.preprocess'));
    $this->assertIdentical(0, $config->get('cache.page.max_age'));
    $this->assertIdentical(TRUE, $config->get('cache.page.use_internal'));
    $this->assertIdentical(TRUE, $config->get('response.gzip'));
  }

}
