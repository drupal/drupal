<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests the Insert query builder with LOB fields.
 *
 * @group Database
 */
class InsertLobTest extends DatabaseTestBase {

  /**
   * Tests that we can insert a single blob field successfully.
   */
  public function testInsertOneBlob(): void {
    $data = "This is\000a test.";
    $this->assertSame(15, strlen($data), 'Test data contains a NULL.');
    $id = $this->connection->insert('test_one_blob')
      ->fields(['blob1' => $data])
      ->execute();
    $r = $this->connection->query('SELECT * FROM {test_one_blob} WHERE [id] = :id', [':id' => $id])->fetchAssoc();
    $this->assertSame($data, $r['blob1'], "Can insert a blob: id $id, " . serialize($r));
  }

  /**
   * Tests that we can insert a null into blob field.
   */
  public function testInsertNullBlob(): void {
    $id = $this->connection->insert('test_one_blob')
      ->fields(['blob1' => NULL])
      ->execute();
    $r = $this->connection->query('SELECT * FROM {test_one_blob} WHERE [id] = :id', [':id' => $id])->fetchAssoc();
    $this->assertNull($r['blob1']);
  }

  /**
   * Tests that we can insert multiple blob fields in the same query.
   */
  public function testInsertMultipleBlob(): void {
    $id = $this->connection->insert('test_two_blobs')
      ->fields([
        'blob1' => 'This is',
        'blob2' => 'a test',
      ])
      ->execute();
    $r = $this->connection->query('SELECT * FROM {test_two_blobs} WHERE [id] = :id', [':id' => $id])->fetchAssoc();
    $this->assertSame('This is', $r['blob1']);
    $this->assertSame('a test', $r['blob2']);
  }

}
