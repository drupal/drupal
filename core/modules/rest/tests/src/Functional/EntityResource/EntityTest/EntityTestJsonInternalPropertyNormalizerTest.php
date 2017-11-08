<?php

namespace Drupal\Tests\rest\Functional\EntityResource\EntityTest;

use Drupal\Core\Cache\Cache;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * Test that internal properties are not exposed in the 'json' format.
 *
 * @group rest
 */
class EntityTestJsonInternalPropertyNormalizerTest extends EntityTestResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $expected = parent::getExpectedNormalizedEntity();
    // The 'internal_value' property in test field type is not exposed in the
    // normalization because setInternal(FALSE) was not called for this
    // property.
    // @see \Drupal\entity_test\Plugin\Field\FieldType\InternalPropertyTestFieldItem::propertyDefinitions
    $expected['field_test_internal'] = [
      [
        'value' => 'This value shall not be internal!',
        'non_internal_value' => 'Computed! This value shall not be internal!',
      ],
    ];
    return $expected;
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    if (!FieldStorageConfig::loadByName('entity_test', 'field_test_internal')) {
      FieldStorageConfig::create([
        'entity_type' => 'entity_test',
        'field_name' => 'field_test_internal',
        'type' => 'internal_property_test',
        'cardinality' => 1,
        'translatable' => FALSE,
      ])->save();
      FieldConfig::create([
        'entity_type' => 'entity_test',
        'field_name' => 'field_test_internal',
        'bundle' => 'entity_test',
        'label' => 'Test field with internal and non-internal properties',
      ])->save();
    }

    $entity = parent::createEntity();
    $entity->field_test_internal = [
      'value' => 'This value shall not be internal!',
    ];
    $entity->save();
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return parent::getNormalizedPostEntity() + [
      'field_test_internal' => [
        [
          'value' => 'This value shall not be internal!',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return Cache::mergeContexts(parent::getExpectedCacheContexts(), ['request_format']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheTags() {
    return Cache::mergeTags(parent::getExpectedCacheTags(), ['you_are_it', 'no_tag_backs']);
  }

}
