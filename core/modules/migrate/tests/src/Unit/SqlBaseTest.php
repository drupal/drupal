<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\SqlBaseTest.
 */

namespace Drupal\Tests\migrate\Unit;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the SqlBase class.
 *
 * @group migrate
 */
class SqlBaseTest extends UnitTestCase {

  /**
   * @param bool $expected_result
   *   The expected result.
   * @param bool $id_map_is_sql
   *   TRUE if we want getIdMap() to return an instance of Sql.
   * @param bool $with_id_map
   *   TRUE if we want the id map to have a valid map of ids.
   * @param array $source_options
   *   An array of connection options for the source connection.
   * @param array $idmap_options
   *   An array of connection options for the id map connection.
   *
   * @dataProvider sqlBaseTestProvider
   */
  public function testMapJoinable($expected_result, $id_map_is_sql, $with_id_map, $source_options = [], $idmap_options = []) {
    // Setup a connection object.
    $source_connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $source_connection->expects($id_map_is_sql && $with_id_map ? $this->once() : $this->never())
      ->method('getConnectionOptions')
      ->willReturn($source_options);

    // Setup the id map connection.
    $idmap_connection = $this->getMockBuilder('Drupal\Core\Database\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $idmap_connection->expects($id_map_is_sql && $with_id_map ? $this->once() : $this->never())
      ->method('getConnectionOptions')
      ->willReturn($idmap_options);

    // Setup the Sql object.
    $sql = $this->getMockBuilder('Drupal\migrate\Plugin\migrate\id_map\Sql')
      ->disableOriginalConstructor()
      ->getMock();
    $sql->expects($id_map_is_sql && $with_id_map ? $this->once() : $this->never())
      ->method('getDatabase')
      ->willReturn($idmap_connection);

    // Setup a migration entity.
    $migration = $this->getMock('Drupal\migrate\Entity\MigrationInterface');
    $migration->expects($with_id_map ? $this->once() : $this->never())
      ->method('getIdMap')
      ->willReturn($id_map_is_sql ? $sql : NULL);

    // Create our SqlBase test class.
    $sql_base = new TestSqlBase();
    $sql_base->setMigration($migration);
    $sql_base->setDatabase($source_connection);

    // Configure the idMap to make the check in mapJoinable() pass.
    if ($with_id_map) {
      $sql_base->setIds([
        'uid' => ['type' => 'integer', 'alias' => 'u'],
      ]);
    }

    $this->assertEquals($expected_result, $sql_base->mapJoinable());
  }

  /**
   * The data provider for SqlBase.
   *
   * @return array
   *   An array of data per test run.
   */
  public function sqlBaseTestProvider() {
    return [
      // Source ids are empty so mapJoinable() is false.
      [FALSE, FALSE, FALSE],
      // Still false because getIdMap() is not a subclass of Sql.
      [FALSE, FALSE, TRUE],
      // Test mapJoinable() returns false when source and id connection options
      // differ.
      [FALSE, TRUE, TRUE, ['username' => 'different_from_map', 'password' => 'different_from_map'], ['username' => 'different_from_source', 'password' => 'different_from_source']],
      // Returns true because source and id map connection options are the same.
      [TRUE, TRUE, TRUE, ['username' => 'same_value', 'password' => 'same_value'], ['username' => 'same_value', 'password' => 'same_value']],
    ];
  }

}

class TestSqlBase extends SqlBase {

  protected $database;
  protected $ids;

  /**
   * Override the constructor so we can create one easily.
   */
  public function __construct() {}

  /**
   * Allows us to set the database during tests.
   *
   * @param mixed $database
   *   The database mock object.
   */
  public function setDatabase($database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatabase() {
    return $this->database;
  }

  /**
   * Allows us to set the migration during the test.
   *
   * @param mixed $migration
   *   The migration mock.
   */
  public function setMigration($migration) {
    $this->migration = $migration;
  }

  /**
   * {@inheritdoc}
   */
  public function mapJoinable() {
    return parent::mapJoinable();
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return $this->ids;
  }

  /**
   * Allows us to set the ids during a test.
   */
  public function setIds($ids) {
    $this->ids = $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {}

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
