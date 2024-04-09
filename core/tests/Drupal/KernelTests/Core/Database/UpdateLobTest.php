<?php

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests the Update query builder with LOB fields.
 *
 * @group Database
 */
class UpdateLobTest extends DatabaseTestBase {

  /**
   * Confirms that we can update a blob column.
   */
  public function testUpdateOneBlob() {
    $data = "This is\000a test.";
    $this->assertSame(15, strlen($data), 'Test data contains a NULL.');
    $id = $this->connection->insert('test_one_blob')
      ->fields(['blob1' => $data])
      ->execute();

    $data .= $data;
    $this->connection->update('test_one_blob')
      ->condition('id', $id)
      ->fields(['blob1' => $data])
      ->execute();

    $r = $this->connection->query('SELECT * FROM {test_one_blob} WHERE [id] = :id', [':id' => $id])->fetchAssoc();
    $this->assertSame($data, $r['blob1'], "Can update a blob: id $id, " . serialize($r));
  }

  /**
   * Tests that we can update a blob column to null.
   */
  public function testUpdateNullBlob() {
    $id = $this->connection->insert('test_one_blob')
      ->fields(['blob1' => 'test'])
      ->execute();
    $r = $this->connection->query('SELECT * FROM {test_one_blob} WHERE [id] = :id', [':id' => $id])->fetchAssoc();
    $this->assertSame('test', $r['blob1']);

    $this->connection->update('test_one_blob')
      ->fields(['blob1' => NULL])
      ->condition('id', $id)
      ->execute();
    $r = $this->connection->query('SELECT * FROM {test_one_blob} WHERE [id] = :id', [':id' => $id])->fetchAssoc();
    $this->assertNull($r['blob1']);
  }

  /**
   * Confirms that we can update two blob columns in the same table.
   */
  public function testUpdateMultipleBlob() {
    $id = $this->connection->insert('test_two_blobs')
      ->fields([
        'blob1' => 'This is',
        'blob2' => 'a test',
      ])
      ->execute();

    $this->connection->update('test_two_blobs')
      ->condition('id', $id)
      ->fields(['blob1' => 'and so', 'blob2' => 'is this'])
      ->execute();

    $r = $this->connection->query('SELECT * FROM {test_two_blobs} WHERE [id] = :id', [':id' => $id])->fetchAssoc();
    $this->assertSame('and so', $r['blob1']);
    $this->assertSame('is this', $r['blob2']);
  }

}
