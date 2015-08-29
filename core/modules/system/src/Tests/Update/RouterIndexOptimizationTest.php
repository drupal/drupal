<?php
/**
 * @file
 * Contains \Drupal\system\Tests\Update\RouterIndexOptimizationTest.
 */

namespace Drupal\system\Tests\Update;

/**
 * Tests system_update_8002().
 *
 * @group Update
 */
class RouterIndexOptimizationTest extends UpdatePathTestBase {
  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Ensures that the system_update_8002() runs as expected.
   */
  public function testUpdate() {
    $this->runUpdates();
    $database = $this->container->get('database');
    // Removed index.
    $this->assertFalse($database->schema()->indexExists(
      'router', 'pattern_outline_fit'
    ));
    // Added index.
    $this->assertTrue($database->schema()->indexExists(
      'router', 'pattern_outline_parts'
    ));
  }

}
