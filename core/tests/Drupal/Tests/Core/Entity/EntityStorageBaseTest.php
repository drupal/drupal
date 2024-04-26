<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityStorageBase
 * @group Entity
 */
class EntityStorageBaseTest extends UnitTestCase {

  /**
   * Generate a mocked entity object.
   *
   * @param string $id
   *   ID value for this entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mocked entity.
   */
  public function generateEntityInterface(string $id): EntityInterface&MockObject {
    $mock_entity = $this->createMock(EntityInterface::class);
    $mock_entity->expects($this->any())
      ->method('id')
      ->willReturn($id);
    return $mock_entity;
  }

  /**
   * Data provider for testLoad().
   */
  public static function providerLoad(): \Generator {
    // Data set for a matching value.
    yield 'matching-value' => ['1', ['1' => '1'], '1'];

    // Data set for no matching value.
    yield 'no-matching-value' => [NULL, [], '0'];
  }

  /**
   * @covers ::load
   *
   * @dataProvider providerLoad
   */
  public function testLoad(string|null $expected, array $entity_fixture, string $query): void {
    if (!is_null($expected)) {
      $expected = $this->generateEntityInterface($expected);
    }
    $entity_fixture = array_map([$this, 'generateEntityInterface'], $entity_fixture);

    $mock_base = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageBase')
      ->disableOriginalConstructor()
      ->onlyMethods(['loadMultiple'])
      ->getMockForAbstractClass();

    // load() always calls loadMultiple().
    $mock_base->expects($this->once())
      ->method('loadMultiple')
      ->with([$query])
      ->willReturn($entity_fixture);

    $this->assertEquals($expected, $mock_base->load($query));
  }

  /**
   * Data provider for testLoadMultiple.
   */
  public static function providerLoadMultiple(): \Generator {
    // Data set for NULL ID parameter.
    yield 'null-id-parameter' => [range(1, 10), range(1, 10), NULL];

    // Data set for no results.
    yield 'no-results' => [[], [], ['11']];

    // Data set for 0 results for multiple IDs.
    yield 'no-results-multiple-ids' => [[], [], ['11', '12', '13']];

    // Data set for 1 result for 1 ID.
    yield '1-result-for-1-id' => [['1' => '1'], ['1' => '1'], ['1']];

    // Data set for results for all IDs.
    $ids = ['1', '2', '3'];
    yield 'results-for-all-ids' => [$ids, $ids, $ids];

    // Data set for partial results for multiple IDs.
    yield 'partial-results-for-multiple-ids' => [$ids, $ids, array_merge($ids, ['11', '12'])];
  }

  /**
   * Test loadMultiple().
   *
   * Does not cover statically-cached results.
   *
   * @covers ::loadMultiple
   *
   * @dataProvider providerLoadMultiple
   */
  public function testLoadMultiple(array $expected, array $load_multiple, array|null $query): void {
    $expected = array_map([$this, 'generateEntityInterface'], $expected);
    $load_multiple = array_map([$this, 'generateEntityInterface'], $load_multiple);

    // Make our EntityStorageBase mock.
    $mock_base = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageBase')
      ->disableOriginalConstructor()
      ->onlyMethods(['doLoadMultiple', 'postLoad'])
      ->getMockForAbstractClass();

    // For all non-cached queries, we call doLoadMultiple().
    $mock_base->expects($this->once())
      ->method('doLoadMultiple')
      ->with($query)
      ->willReturn($load_multiple);

    // Make our EntityTypeInterface mock so that we can turn off static caching.
    $mock_entity_type = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    // Disallow caching.
    $mock_entity_type->expects($this->any())
      ->method('isStaticallyCacheable')
      ->willReturn(FALSE);
    // Add the EntityTypeInterface to the storage object.
    $ref_entity_type = new \ReflectionProperty($mock_base, 'entityType');
    $ref_entity_type->setValue($mock_base, $mock_entity_type);

    // Set up expectations for postLoad(), which we only call if there are
    // results from loadMultiple().
    $mock_base->expects($this->exactly(empty($load_multiple) ? 0 : 1))
      ->method('postLoad');

    $this->assertEquals($expected, $mock_base->loadMultiple($query));
  }

}
