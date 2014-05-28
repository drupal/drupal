<?php
/**
 * @file
 * Contains \Drupal\migrate\Tests\process\DedupeEntityTest.
 */

namespace Drupal\migrate\Tests\process;

use Drupal\migrate\Plugin\migrate\process\DedupeEntity;

/**
 * Test the deduplication entity process plugin.
 *
 * @group migrate
 * @group Drupal
 *
 * @see \Drupal\migrate\Plugin\migrate\process\DedupeEntity
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
   * @var  \Drupal\Core\Entity\Query\QueryFactory|\PHPUnit_Framework_MockObject_MockObject
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
  public static function getInfo() {
    return array(
      'name' => 'Dedupe entity process plugin',
      'description' => 'Tests the entity deduplication process plugin.',
      'group' => 'Migrate',
    );
  }

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
  public function testDedupe($count, $postfix = '') {
    $configuration = array(
      'entity_type' => 'test_entity_type',
      'field' => 'test_field',
    );
    if ($postfix) {
      $configuration['postfix'] = $postfix;
    }
    $plugin = new DedupeEntity($configuration, 'dedupe_entity', array(), $this->getMigration(), $this->entityQueryFactory);
    $this->entityQueryExpects($count);
    $return = $plugin->transform('test', $this->migrateExecutable, $this->row, 'testproperty');
    $this->assertSame($return, 'test' . ($count ? $postfix . $count : ''));
  }

  /**
   * Data provider for testDedupe().
   */
  public function providerTestDedupe() {
    return array(
      // Tests the entity deduplication plugin when there is no duplication
      // and no postfix.
      array(0),
      // Tests the entity deduplication plugin when there is duplication but
      // no postfix.
      array(3),
      // Tests the entity deduplication plugin when there is no duplication
      // but there is a postfix.
      array(0, '_'),
      // Tests the entity deduplication plugin when there is duplication and
      // there is a postfix.
      array(2, '_'),
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
