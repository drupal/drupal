<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for Block Content reusable column index.
 *
 * @group block_content
 */
class BlockContentReusableIndexUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests the upgrade path for Block Content reusable index.
   */
  public function testRunUpdates(): void {
    $connection = Database::getConnection();
    $this->assertFalse($connection->schema()->indexExists('block_content_field_data', 'block_content_field__reusable'), 'Block Content reusable index not yet added.');
    $this->runUpdates();
    $this->assertTrue($connection->schema()->indexExists('block_content_field_data', 'block_content_field__reusable'), 'Block Content reusable index has been added.');
  }

}
