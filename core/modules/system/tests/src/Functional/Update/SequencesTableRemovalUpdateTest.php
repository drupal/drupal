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
      // @todo Use 11.3.0 dump only when https://www.drupal.org/i/3569127 lands.
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/uninstall-history.php',
      __DIR__ . '/../../../../../system/tests/fixtures/update/uninstall-contact.php',
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
