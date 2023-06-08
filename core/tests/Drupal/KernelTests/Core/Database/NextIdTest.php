<?php

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests the sequences API.
 *
 * @group Database
 * @group legacy
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

    $table_specification = [
      'description' => 'Stores IDs.',
      'fields' => [
        'value' => [
          'description' => 'The value of the sequence.',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['value'],
    ];
    $this->connection->schema()->createTable('sequences', $table_specification);
  }

  /**
   * Tests that the sequences API works.
   */
  public function testDbNextId() {
    $this->expectDeprecation('Drupal\Core\Database\Connection::nextId() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Modules should use instead the keyvalue storage for the last used id. See https://www.drupal.org/node/3349345');

    $first = $this->connection->nextId();
    $second = $this->connection->nextId();
    // We can test for exact increase in here because we know there is no
    // other process operating on these tables -- normally we could only
    // expect $second > $first.
    $this->assertEquals($first + 1, $second, 'The second call from a sequence provides a number increased by one.');
    $result = $this->connection->nextId(1000);
    $this->assertEquals(1001, $result, 'Sequence provides a larger number than the existing ID.');
  }

}
