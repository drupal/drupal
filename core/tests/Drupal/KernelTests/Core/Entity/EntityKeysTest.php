<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Test the behavior of entity keys.
 *
 * @group entity
 */
class EntityKeysTest extends EntityKernelTestBase {

  /**
   * Test the cache when multiple keys reference a single field.
   *
   * @dataProvider multipleKeysCacheTestCases
   */
  public function testMultipleKeysCache($translatable) {
    $this->state->set('entity_test.additional_base_field_definitions', [
      'test_field' => BaseFieldDefinition::create('string')->setTranslatable($translatable),
    ]);
    $this->state->set('entity_test.entity_keys', [
      'key_1' => 'test_field',
      'key_2' => 'test_field',
    ]);
    drupal_flush_all_caches();
    $this->installEntitySchema('entity_test');

    $entity = EntityTest::create([]);

    $entity->set('test_field', 'foo');
    $this->assertEquals('foo', $entity->getEntityKey('key_1'));
    $this->assertEquals('foo', $entity->getEntityKey('key_2'));

    $entity->set('test_field', 'bar');
    $this->assertEquals('bar', $entity->getEntityKey('key_1'));
    $this->assertEquals('bar', $entity->getEntityKey('key_2'));
  }

  /**
   * Data provider for ::testMultipleKeysCache.
   */
  public function multipleKeysCacheTestCases() {
    return [
      'translatable Entity Key' => [
        TRUE,
      ],
      'Non-translatable entity key' => [
        FALSE,
      ],
    ];
  }

}
