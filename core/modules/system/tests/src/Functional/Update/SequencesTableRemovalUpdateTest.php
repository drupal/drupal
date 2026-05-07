<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the upgrade path for removing the sequences table.
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
class SequencesTableRemovalUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that system_update_12000() removes the deprecated sequences table.
   */
  public function testRunUpdates(): void {
    $connection = Database::getConnection();
    $this->assertTrue($connection->schema()->tableExists('sequences'));
    $this->runUpdates();
    $this->assertFalse($connection->schema()->tableExists('sequences'));
  }

}
