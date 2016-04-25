<?php

namespace Drupal\Tests\system\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade performance variables to system.*.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateSystemPerformanceTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_system_performance');
  }

  /**
   * Tests migration of system (Performance) variables to system.performance.yml.
   */
  public function testSystemPerformance() {
    $config = $this->config('system.performance');
    $this->assertIdentical(FALSE, $config->get('css.preprocess'));
    $this->assertIdentical(FALSE, $config->get('js.preprocess'));
    $this->assertIdentical(0, $config->get('cache.page.max_age'));
  }

}
