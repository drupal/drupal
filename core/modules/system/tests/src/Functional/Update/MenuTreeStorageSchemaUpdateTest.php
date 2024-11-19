<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\Connection;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update of menu tree storage fields.
 *
 * @group system
 */
class MenuTreeStorageSchemaUpdateTest extends UpdatePathTestBase {

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    /** @var \Drupal\Core\Database\Connection $connection */
    $this->connection = \Drupal::service('database');
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      // Start with a bare installation of Drupal 10.3.0.
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests DB behavior after update.
   */
  public function testSchemaLengthAfterUpdate(): void {
    if (\Drupal::service('database')->databaseType() == 'sqlite') {
      $this->markTestSkipped("This test does not support the SQLite database driver.");
    }

    $results = $this->connection->query('SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = :menu AND COLUMN_NAME IN ( :column_names[] )', [
      ':menu' => $this->connection->schema()->prefixNonTable('menu_tree'),
      ':column_names[]' => ['route_param_key', 'url'],
    ])->fetchCol();
    $this->assertNotEmpty($results);
    foreach ($results as $result) {
      self::assertEquals(255, $result);
    }

    $this->runUpdates();

    $results = $this->connection->query('SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = :menu AND COLUMN_NAME IN ( :column_names[] )', [
      ':menu' => $this->connection->schema()->prefixNonTable('menu_tree'),
      ':column_names[]' => ['route_param_key', 'url'],
    ])->fetchCol();
    $this->assertNotEmpty($results);
    foreach ($results as $result) {
      self::assertEquals(2048, $result);
    }
  }

}
