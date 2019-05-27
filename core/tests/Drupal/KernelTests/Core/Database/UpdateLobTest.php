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
    $this->assertTrue(strlen($data) === 15, 'Test data contains a NULL.');
    $id = $this->connection->insert('test_one_blob')
      ->fields(['blob1' => $data])
      ->execute();

    $data .= $data;
    $this->connection->update('test_one_blob')
      ->condition('id', $id)
      ->fields(['blob1' => $data])
      ->execute();

    $r = $this->connection->query('SELECT * FROM {test_one_blob} WHERE id = :id', [':id' => $id])->fetchAssoc();
    $this->assertTrue($r['blob1'] === $data, format_string('Can update a blob: id @id, @data.', ['@id' => $id, '@data' => serialize($r)]));
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

    $r = $this->connection->query('SELECT * FROM {test_two_blobs} WHERE id = :id', [':id' => $id])->fetchAssoc();
    $this->assertTrue($r['blob1'] === 'and so' && $r['blob2'] === 'is this', 'Can update multiple blobs per row.');
  }

}
