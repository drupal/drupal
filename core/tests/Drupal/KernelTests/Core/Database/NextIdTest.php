<?php

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests the sequences API.
 *
 * @group Database
 */
class NextIdTest extends DatabaseTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = ['database_test', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('system', 'sequences');
  }

  /**
   * Tests that the sequences API works.
   */
  public function testDbNextId() {
    $first = $this->connection->nextId();
    $second = $this->connection->nextId();
    // We can test for exact increase in here because we know there is no
    // other process operating on these tables -- normally we could only
    // expect $second > $first.
    $this->assertEqual($first + 1, $second, 'The second call from a sequence provides a number increased by one.');
    $result = $this->connection->nextId(1000);
    $this->assertEqual($result, 1001, 'Sequence provides a larger number than the existing ID.');
  }

}
