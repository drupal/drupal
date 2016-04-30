<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\Plugin\migrate\process\DedupeEntity;
use Drupal\Component\Utility\Unicode;

/**
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\DedupeEntity
 * @group migrate
 */
class DedupeEntityTest extends MigrateProcessTestCase {

  /**
   * The mock entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The mock entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityQueryFactory;

  /**
   * The migration configuration, initialized to set the ID to test.
   *
   * @var array
   */
  protected $migrationConfiguration = array(
    'id' => 'test',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityQuery = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityQueryFactory = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityQueryFactory->expects($this->any())
      ->method('get')
      ->will($this->returnValue($this->entityQuery));
    parent::setUp();
  }

  /**
   * Tests entity based deduplication based on providerTestDedupe() values.
   *
   * @dataProvider providerTestDedupe
   */
  public function testDedupe($count, $postfix = '', $start = NULL, $length = NULL) {
    $configuration = array(
      'entity_type' => 'test_entity_type',
      'field' => 'test_field',
    );
    if ($postfix) {
      $configuration['postfix'] = $postfix;
    }
    $configuration['start'] = isset($start) ? $start : NULL;
    $configuration['length'] = isset($length) ? $length : NULL;
    $plugin = new DedupeEntity($configuration, 'dedupe_entity', array(), $this->getMigration(), $this->entityQueryFactory);
    $this->entityQueryExpects($count);
    $value = $this->randomMachineName(32);
    $actual = $plugin->transform($value, $this->migrateExecutable, $this->row, 'testproperty');
    $expected = Unicode::substr($value, $start, $length);
    $expected .= $count ? $postfix . $count : '';
    $this->assertSame($expected, $actual);
  }

  /**
   * Tests that invalid start position throws an exception.
   */
  public function testDedupeEntityInvalidStart() {
    $configuration = array(
      'entity_type' => 'test_entity_type',
      'field' => 'test_field',
      'start' => 'foobar',
    );
    $plugin = new DedupeEntity($configuration, 'dedupe_entity', array(), $this->getMigration(), $this->entityQueryFactory);
    $this->setExpectedException('Drupal\migrate\MigrateException', 'The start position configuration key should be an integer. Omit this key to capture from the beginning of the string.');
    $plugin->transform('test_start', $this->migrateExecutable, $this->row, 'testproperty');
  }

  /**
   * Tests that invalid length option throws an exception.
   */
  public function testDedupeEntityInvalidLength() {
    $configuration = array(
      'entity_type' => 'test_entity_type',
      'field' => 'test_field',
      'length' => 'foobar',
    );
    $plugin = new DedupeEntity($configuration, 'dedupe_entity', array(), $this->getMigration(), $this->entityQueryFactory);
    $this->setExpectedException('Drupal\migrate\MigrateException', 'The character length configuration key should be an integer. Omit this key to capture the entire string.');
    $plugin->transform('test_length', $this->migrateExecutable, $this->row, 'testproperty');
  }

  /**
   * Data provider for testDedupe().
   */
  public function providerTestDedupe() {
    return array(
      // Tests no duplication.
      array(0),
      // Tests no duplication and start position.
      array(0, NULL, 10),
      // Tests no duplication, start position, and length.
      array(0, NULL, 5, 10),
      // Tests no duplication and length.
      array(0, NULL, NULL, 10),
      // Tests duplication.
      array(3),
      // Tests duplication and start position.
      array(3, NULL, 10),
      // Tests duplication, start position, and length.
      array(3, NULL, 5, 10),
      // Tests duplication and length.
      array(3, NULL, NULL, 10),
      // Tests no duplication and postfix.
      array(0, '_'),
      // Tests no duplication, postfix, and start position.
      array(0, '_', 5),
      // Tests no duplication, postfix, start position, and length.
      array(0, '_', 5, 10),
      // Tests no duplication, postfix, and length.
      array(0, '_', NULL, 10),
      // Tests duplication and postfix.
      array(2, '_'),
      // Tests duplication, postfix, and start position.
      array(2, '_', 5),
      // Tests duplication, postfix, start position, and length.
      array(2, '_', 5, 10),
      // Tests duplication, postfix, and length.
      array(2, '_', NULL, 10),
    );
  }

  /**
   * Helper function to add expectations to the mock entity query object.
   *
   * @param int $count
   *   The number of deduplications to be set up.
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
      ->will($this->returnCallback(function () use (&$count) { return $count--;}));
  }
}
