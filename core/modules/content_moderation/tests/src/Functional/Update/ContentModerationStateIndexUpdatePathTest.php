<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the upgrade path for adding an index to moderation state column.
 */
#[Group('content_moderation')]
#[RunTestsInSeparateProcesses]
class ContentModerationStateIndexUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/content-moderation.php',
    ];
  }

  /**
   * Tests the upgrade path for moderation state index.
   */
  public function testRunUpdates(): void {
    $table = 'content_moderation_state_field_revision';
    $name = 'content_moderation_state__moderation_state';

    $connection = Database::getConnection();

    $this->assertFalse($connection->schema()->indexExists($table, $name));

    $this->runUpdates();

    $this->assertTrue($connection->schema()->indexExists($table, $name));
  }

}
