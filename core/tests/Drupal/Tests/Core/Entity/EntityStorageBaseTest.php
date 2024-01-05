<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Tests\UnitTestCase;

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
  public function generateEntityInterface($id) {
    $mock_entity = $this->getMockBuilder('\Drupal\Core\Entity\EntityInterface')
      ->onlyMethods(['id'])
      ->getMockForAbstractClass();
    $mock_entity->expects($this->any())
      ->method('id')
      ->willReturn((string) $id);
    return $mock_entity;
  }

  /**
   * Data provider for testLoad().
   *
   * @return array
   *   - Expected output of load().
   *   - A fixture of entities to query against. Suitable return value for
   *     loadMultiple().
   *   - The ID we'll query.
   */
  public function providerLoad() {
    $data = [];

    // Data set for a matching value.
    $entity = $this->generateEntityInterface('1');
    $data['matching-value'] = [$entity, ['1' => $entity], '1'];

    // Data set for no matching value.
    $data['no-matching-value'] = [NULL, [], '0'];

    return $data;
  }

  /**
   * @covers ::load
   *
   * @dataProvider providerLoad
   */
  public function testLoad($expected, $entity_fixture, $query) {
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
   *
   * @return array
   *   - The expected result.
   *   - Results for doLoadMultiple(), called internally by loadMultiple().
   *   - The query, an array of IDs.
   */
  public function providerLoadMultiple() {
    // Create a fixture of entity objects.
    $fixture = [];
    foreach (range(1, 10) as $index) {
      $fixture[(string) $index] = $this->generateEntityInterface($index);
    }

    $data = [];

    // Data set for NULL ID parameter.
    $data['null-id-parameter'] = [$fixture, $fixture, NULL];

    // Data set for no results.
    $data['no-results'] = [[], [], ['11']];

    // Data set for 0 results for multiple IDs.
    $data['no-results-multiple-ids'] = [[], [], ['11', '12', '13']];

    // Data set for 1 result for 1 ID.
    $data['1-result-for-1-id'] = [
      ['1' => $fixture['1']],
      ['1' => $fixture['1']],
      ['1'],
    ];

    // Data set for results for all IDs.
    $ids = ['1', '2', '3'];
    foreach ($ids as $id) {
      $expectation[$id] = $fixture[$id];
      $load_multiple[$id] = $fixture[$id];
    }
    $data['results-for-all-ids'] = [$expectation, $load_multiple, $ids];

    // Data set for partial results for multiple IDs.
    $ids = ['1', '2', '3'];
    foreach ($ids as $id) {
      $expectation[$id] = $fixture[$id];
      $load_multiple[$id] = $fixture[$id];
    }
    $ids = array_merge($ids, ['11', '12']);
    $data['partial-results-for-multiple-ids'] = [
      $expectation,
      $load_multiple,
      $ids,
    ];

    return $data;
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
  public function testLoadMultiple($expected, $load_multiple, $query) {
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
    $mock_entity_type = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeInterface')
      ->onlyMethods(['isStaticallyCacheable'])
      ->getMockForAbstractClass();
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
