<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\migrate\Plugin\migrate\process\MakeUniqueEntityField;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\MakeUniqueEntityField
 * @group migrate
 */
class MakeUniqueEntityFieldTest extends MigrateProcessTestCase {

  /**
   * The mock entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * The migration configuration, initialized to set the ID to test.
   *
   * @var array
   */
  protected $migrationConfiguration = [
    'id' => 'test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityQuery = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->any())
      ->method('getQuery')
      ->willReturn($this->entityQuery);
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('test_entity_type')
      ->willReturn($storage);
    parent::setUp();
  }

  /**
   * Tests making an entity field value unique.
   *
   * @dataProvider providerTestMakeUniqueEntityField
   */
  public function testMakeUniqueEntityField($count, $postfix = '', $start = NULL, $length = NULL) {
    $configuration = [
      'entity_type' => 'test_entity_type',
      'field' => 'test_field',
    ];
    if ($postfix) {
      $configuration['postfix'] = $postfix;
    }
    $configuration['start'] = isset($start) ? $start : NULL;
    $configuration['length'] = isset($length) ? $length : NULL;
    $plugin = new MakeUniqueEntityField($configuration, 'make_unique', [], $this->getMigration(), $this->entityTypeManager);
    $this->entityQueryExpects($count);
    $value = $this->randomMachineName(32);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'testproperty');
    $expected = mb_substr($value, $start, $length);
    $expected .= $count ? $postfix . $count : '';
    $this->assertSame($expected, $actual);
  }

  /**
   * Tests that invalid start position throws an exception.
   */
  public function testMakeUniqueEntityFieldEntityInvalidStart() {
    $configuration = [
      'entity_type' => 'test_entity_type',
      'field' => 'test_field',
      'start' => 'foobar',
    ];
    $plugin = new MakeUniqueEntityField($configuration, 'make_unique', [], $this->getMigration(), $this->entityTypeManager);
    $this->expectException('Drupal\migrate\MigrateException');
    $this->expectExceptionMessage('The start position configuration key should be an integer. Omit this key to capture from the beginning of the string.');
    $plugin->transform('test_start', $this->migrateExecutable, $this->row, 'testproperty');
  }

  /**
   * Tests that invalid length option throws an exception.
   */
  public function testMakeUniqueEntityFieldEntityInvalidLength() {
    $configuration = [
      'entity_type' => 'test_entity_type',
      'field' => 'test_field',
      'length' => 'foobar',
    ];
    $plugin = new MakeUniqueEntityField($configuration, 'make_unique', [], $this->getMigration(), $this->entityTypeManager);
    $this->expectException('Drupal\migrate\MigrateException');
    $this->expectExceptionMessage('The character length configuration key should be an integer. Omit this key to capture the entire string.');
    $plugin->transform('test_length', $this->migrateExecutable, $this->row, 'testproperty');
  }

  /**
   * Data provider for testMakeUniqueEntityField().
   */
  public function providerTestMakeUniqueEntityField() {
    return [
      // Tests no duplication.
      [0],
      // Tests no duplication and start position.
      [0, NULL, 10],
      // Tests no duplication, start position, and length.
      [0, NULL, 5, 10],
      // Tests no duplication and length.
      [0, NULL, NULL, 10],
      // Tests duplication.
      [3],
      // Tests duplication and start position.
      [3, NULL, 10],
      // Tests duplication, start position, and length.
      [3, NULL, 5, 10],
      // Tests duplication and length.
      [3, NULL, NULL, 10],
      // Tests no duplication and postfix.
      [0, '_'],
      // Tests no duplication, postfix, and start position.
      [0, '_', 5],
      // Tests no duplication, postfix, start position, and length.
      [0, '_', 5, 10],
      // Tests no duplication, postfix, and length.
      [0, '_', NULL, 10],
      // Tests duplication and postfix.
      [2, '_'],
      // Tests duplication, postfix, and start position.
      [2, '_', 5],
      // Tests duplication, postfix, start position, and length.
      [2, '_', 5, 10],
      // Tests duplication, postfix, and length.
      [2, '_', NULL, 10],
    ];
  }

  /**
   * Helper function to add expectations to the mock entity query object.
   *
   * @param int $count
   *   The number of unique values to be set up.
   */
  protected function entityQueryExpects($count) {
    $this->entityQuery->expects($this->exactly($count + 1))
      ->method('condition')
      ->will($this->returnValue($this->entityQuery));
    $this->entityQuery->expects($this->exactly($count + 1))
      ->method('count')
      ->will($this->returnValue($this->entityQuery));
    $this->entityQuery->expects($this->exactly($count + 1))
      ->method('execute')
      ->will($this->returnCallback(function () use (&$count) {
        return $count--;
      }));
  }

  /**
   * Tests making an entity field value unique only for migrated entities.
   */
  public function testMakeUniqueEntityFieldMigrated() {
    $configuration = [
      'entity_type' => 'test_entity_type',
      'field' => 'test_field',
      'migrated' => TRUE,
    ];
    $plugin = new MakeUniqueEntityField($configuration, 'make_unique', [], $this->getMigration(), $this->entityTypeManager);

    // Setup the entityQuery used in MakeUniqueEntityFieldEntity::exists. The
    // map, $map, is an array consisting of the four input parameters to the
    // query condition method and then the query to return. Both 'forum' and
    // 'test_vocab' are existing entities. There is no 'test_vocab1'.
    $map = [];
    foreach (['forums', 'test_vocab', 'test_vocab1'] as $id) {
      $query = $this->prophesize(QueryInterface::class);
      $query->willBeConstructedWith([]);
      $query->execute()->willReturn($id === 'test_vocab1' ? [] : [$id]);
      $map[] = ['test_field', $id, NULL, NULL, $query->reveal()];
    }
    $this->entityQuery
      ->method('condition')
      ->will($this->returnValueMap($map));

    // Entity 'forums' is pre-existing, entity 'test_vocab' was migrated.
    $this->idMap
      ->method('lookupSourceId')
      ->will($this->returnValueMap([
        [['test_field' => 'forums'], FALSE],
        [['test_field' => 'test_vocab'], ['source_id' => 42]],
      ]));

    // Existing entity 'forums' was not migrated, value should not be unique.
    $actual = $plugin->transform('forums', $this->migrateExecutable, $this->row, 'testproperty');
    $this->assertEquals('forums', $actual, 'Pre-existing name is re-used');

    // Entity 'test_vocab' was migrated, value should be unique.
    $actual = $plugin->transform('test_vocab', $this->migrateExecutable, $this->row, 'testproperty');
    $this->assertEquals('test_vocab1', $actual, 'Migrated name is deduplicated');
  }

}
