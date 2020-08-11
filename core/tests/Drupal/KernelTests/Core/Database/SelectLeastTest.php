<?php

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests the SQL LEAST operator.
 *
 * @group Database
 */
class SelectLeastTest extends DatabaseTestBase {

  /**
   * Tests the SQL LEAST operator.
   *
   * @dataProvider selectLeastProvider
   */
  public function testSelectLeast($values, $expected) {
    $least = $this->connection->query("SELECT LEAST(:values[])", [':values[]' => $values])->fetchField();
    $this->assertEquals($expected, $least);
  }

  public function selectLeastProvider() {
    return [
      [[1, 2, 3, 4, 5, 6], 1],
      [['A', 'B', 'C', 'NULL', 'F'], 'A'],
      [['NULL', 'NULL'], 'NULL'],
      [['TRUE', 'FALSE'], 'FALSE'],
      [['A', 'B', 'C', 'NULL'], 'A'],
    ];
  }

}
