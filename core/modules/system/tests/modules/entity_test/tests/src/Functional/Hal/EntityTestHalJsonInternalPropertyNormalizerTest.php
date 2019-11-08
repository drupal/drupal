<?php

namespace Drupal\Tests\entity_test\Functional\Hal;

use Drupal\Core\Cache\Cache;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\hal\Functional\EntityResource\HalEntityNormalizationTrait;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * Test that internal properties are not exposed in the 'hal_json' format.
 *
 * @group hal
 */
class EntityTestHalJsonInternalPropertyNormalizerTest extends EntityTestHalJsonAnonTest {

  use AnonResourceTestTrait, HalEntityNormalizationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $default_normalization = parent::getExpectedNormalizedEntity();

    $normalization = $this->applyHalFieldNormalization($default_normalization);
    // The 'internal_value' property in test field type will not be returned in
    // normalization because setInternal(FALSE) was not called for this
    // property.
    // @see \Drupal\entity_test\Plugin\Field\FieldType\InternalPropertyTestFieldItem::propertyDefinitions
    $normalization['field_test_internal'] = [
      [
        'value' => 'This value shall not be internal!',
        'non_internal_value' => 'Computed! This value shall not be internal!',
      ],
    ];
    return $normalization;
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
