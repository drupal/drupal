<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for the router table update.
 *
 * @group Update
 */
class RouteAliasUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests the upgrade path for adding aliases to the router table.
   */
  public function testRunUpdates(): void {
    $connection = Database::getConnection();
    $this->assertFalse($connection->schema()->fieldExists('router', 'alias'));
    $this->runUpdates();
    $this->assertTrue($connection->schema()->fieldExists('router', 'alias'));
    $this->assertTrue($connection->schema()->indexExists('router', 'alias'));
  }

}
