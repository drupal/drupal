<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\SqlBaseTest.
 */

namespace Drupal\Tests\migrate\Unit;

use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the SqlBase class.
 *
 * @group migrate
 */
class SqlBaseTest extends UnitTestCase {

  /**
   * The default configuration array.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The default plugin_id string.
   *
   * @var string
   */
  protected $pluginId = '';

  /**
   * The default plugin_definition array.
   *
   * @var array
   */
  protected $pluginDefinition = [];

  /**
   * Tests that source conditions are recognized.
   *
   * @param array|string $conditions
   *   Source conditions.
   *
   * @dataProvider sqlBaseConstructorTestProvider
   */
  public function testConstructor($conditions) {
    $configuration = [
      'conditions' => $conditions,
    ];
    // Setup the migration interface.
    $migration = $this->getMockBuilder(MigrationInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Setup the state object.
    $state = $this->getMockBuilder(StateInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Test with an invalid process pipeline.
    $this->expectException(\InvalidArgumentException::class);
    new TestSqlBase($configuration, $this->pluginId, $this->pluginDefinition, $migration, $state);
  }

  /**
   * The data provider for testConstructor.
   */
  public function sqlBaseConstructorTestProvider() {
    return [
      'not array' => [
        'conditions' => '',
      ],
      'not multidimensional array' => [
        'conditions' => [''],
      ],
      'field not specified' => [
        'conditions' => [['']],
      ],
    ];
  }

  /**
   * Tests that the ID map is joinable.
   *
   * @param bool $expected_result
   *   The expected result.
   * @param bool $id_map_is_sql
   *   TRUE if we want getIdMap() to return an instance of Sql.
   * @param bool $with_id_map
   *   TRUE if we want the ID map to have a valid map of IDs.
   * @param array $source_options
   *   (optional) An array of connection options for the source connection.
   *   Defaults to an empty array.
   * @param array $idmap_options
   *   (optional) An array of connection options for the ID map connection.
   *   Defaults to an empty array.
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

    // Setup the ID map connection.
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

    // Setup the State object.
    $state = $this->getMockBuilder(StateInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Setup a migration entity.
    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->atLeastOnce())
      ->method('getIdMap')
      ->willReturn($id_map_is_sql ? $sql : NULL);

    // Create our SqlBase test class.
    $sql_base = new TestSqlBase($this->configuration, $this->pluginId, $this->pluginDefinition, $migration, $state);
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
      [
        FALSE,
        FALSE,
        FALSE,
      ],
      // Still false because getIdMap() is not a subclass of Sql.
      [
        FALSE,
        FALSE,
        TRUE,
      ],
      // Test mapJoinable() returns false when source and id connection options
      // differ.
      [
        FALSE,
        TRUE,
        TRUE,
        ['driver' => 'mysql', 'username' => 'different_from_map', 'password' => 'different_from_map'],
        ['driver' => 'mysql', 'username' => 'different_from_source', 'password' => 'different_from_source'],
      ],
      // Returns false because driver is pgsql and the databases are not the
      // same.
      [
        FALSE,
        TRUE,
        TRUE,
        ['driver' => 'pgsql', 'database' => '1.pgsql', 'username' => 'same_value', 'password' => 'same_value'],
        ['driver' => 'pgsql', 'database' => '2.pgsql', 'username' => 'same_value', 'password' => 'same_value'],
      ],
      // Returns false because driver is sqlite and the databases are not the
      // same.
      [
        FALSE,
        TRUE,
        TRUE,
        ['driver' => 'sqlite', 'database' => '1.sqlite', 'username' => '', 'password' => ''],
        ['driver' => 'sqlite', 'database' => '2.sqlite', 'username' => '', 'password' => ''],
      ],
      // Returns false because driver is not the same.
      [
        FALSE,
        TRUE,
        TRUE,
        ['driver' => 'pgsql', 'username' => 'same_value', 'password' => 'same_value'],
        ['driver' => 'mysql', 'username' => 'same_value', 'password' => 'same_value'],
      ],
    ];
  }

  /**
   * Test prepare query for valid condition.
   *
   * @param array $conditions
   *   Source conditions.
   *
   * @dataProvider prepareQueryTestProvider
   */
  public function testPrepareQuery($conditions) {
    $migration = $this->getMockBuilder(MigrationInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    // Setup the state object.
    $state = $this->getMockBuilder(StateInterface::class)
      ->disableOriginalConstructor()
      ->getMock();

    $configuration['conditions'] = $conditions['condition'];
    $expected_result = $conditions['expected_result'];

    foreach ($configuration['conditions'] as $condition) {
      $result[] = [$condition['field'], $condition['value'] ?? NULL, $condition['operator'] ?? '='];
    }
    // For multiple result.
    $this->assertEquals($expected_result, $result);
    new TestSqlBase($configuration, $this->pluginId, $this->pluginDefinition, $migration, $state);
  }

  /**
   * The data provider for testPrepareQuery.
   */
  public function prepareQueryTestProvider() {
    return [
      'field value operator condition' => [
        'data' => [
           'condition' => [
            [
              'field' => 'nid',
              'value' => '3',
              'operator' => '>',
            ],
          ],
           'expected_result' => [
            ['nid', '3', '>'],
          ],
       ],
      ],
      'default operator condition' => [
        'data' => [
         'condition' => [
            [
              'field' => 'type',
              'value' => 'article',
            ],
          ],
          'expected_result' => [
            ['type', 'article', '='],
          ],
         ],
      ],
      'default value null condition' => [
        'data' => [
          'condition' => [
            [
              'field' => 'langcode',
              'operator' => 'IS',
            ],
          ],
          'expected_result' => [
            ['langcode', NULL, 'IS'],
          ],
         ],
      ],
      'field value operator multiple condition' => [
        'data' => [
           'condition' => [
            [
              'field' => 'nid',
              'value' => '3',
              'operator' => '>',
            ],
            [
              'field' => 'title',
              'operator' => 'IS',
            ],
          ],
           'expected_result' => [
            ['nid', '3', '>'],
            ['title', NULL, 'IS'],
          ],
       ],
      ],
    ];
  }

}

/**
 * Creates a base source class for SQL migration testing.
 */
class TestSqlBase extends SqlBase {

  /**
   * The database object.
   *
   * @var object
   */
  protected $database;

  /**
   * The migration IDs.
   *
   * @var array
   */
  protected $ids;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
  }

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
   * Allows us to set the IDs during a test.
   *
   * @param array $ids
   *   An array of identifiers.
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
