<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests the Insert query builder with LOB fields.
 *
 * @group Database
 */
class InsertLobTest extends DatabaseTestBase {

  /**
   * Tests that we can insert a single blob field successfully.
   */
  public function testInsertOneBlob() {
    $data = "This is\000a test.";
    $this->assertTrue(strlen($data) === 15, 'Test data contains a NULL.');
    $id = $this->connection->insert('test_one_blob')
      ->fields(['blob1' => $data])
      ->execute();
    $r = $this->connection->query('SELECT * FROM {test_one_blob} WHERE [id] = :id', [':id' => $id])->fetchAssoc();
    $this->assertTrue($r['blob1'] === $data, new FormattableMarkup('Can insert a blob: id @id, @data.', ['@id' => $id, '@data' => serialize($r)]));
  }

  /**
   * Tests that we can insert multiple blob fields in the same query.
   */
  public function testInsertMultipleBlob() {
    $id = $this->connection->insert('test_two_blobs')
      ->fields([
        'blob1' => 'This is',
        'blob2' => 'a test',
      ])
      ->execute();
    $r = $this->connection->query('SELECT * FROM {test_two_blobs} WHERE [id] = :id', [':id' => $id])->fetchAssoc();
    $this->assertTrue($r['blob1'] === 'This is' && $r['blob2'] === 'a test', 'Can insert multiple blobs per row.');
  }

}
