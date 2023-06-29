<?php

declare(strict_types=1);

namespace Drupal\Tests\forum\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests addition of the forum_index primary key.
 *
 * @group forum
 */
final class ForumIndexUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      dirname(__DIR__, 2) . '/fixtures/update/drupal-10.1.0.empty.standard.forum.gz',
    ];
  }

  /**
   * Tests the update path to add the new primary key.
   */
  public function testUpdatePath(): void {
    $schema = \Drupal::database()->schema();
    $this->assertFalse($schema->indexExists('forum_index', 'PRIMARY'));
    $this->runUpdates();
    $this->assertTrue($schema->indexExists('forum_index', 'PRIMARY'));
  }

}
