<?php

declare(strict_types=1);

namespace Drupal\Tests\path_alias\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the update path for the path_alias_revision table indices.
 */
#[Group('path_alias')]
#[RunTestsInSeparateProcesses]
class PathAliasRevisionIndexesUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests the update path for the path_alias_revision table indices.
   */
  public function testRunUpdates(): void {
    $schema = \Drupal::database()->schema();

    $this->assertFalse($schema->indexExists('path_alias_revision', 'path_alias_revision__alias_langcode_id_status'));
    $this->assertFalse($schema->indexExists('path_alias_revision', 'path_alias_revision__path_langcode_id_status'));

    $this->runUpdates();

    $this->assertTrue($schema->indexExists('path_alias_revision', 'path_alias_revision__alias_langcode_id_status'));
    $this->assertTrue($schema->indexExists('path_alias_revision', 'path_alias_revision__path_langcode_id_status'));
  }

}
