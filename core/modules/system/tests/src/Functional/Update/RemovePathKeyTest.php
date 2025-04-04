<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for the system.file schema update.
 *
 * @group Update
 */
class RemovePathKeyTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests the upgrade path for removing system.file.path key.
   */
  public function testRunUpdates(): void {
    $this->assertIsArray(\Drupal::config('system.file')->get('path'));
    $this->runUpdates();
    $this->assertNull(\Drupal::config('system.file')->get('path'));
  }

}
