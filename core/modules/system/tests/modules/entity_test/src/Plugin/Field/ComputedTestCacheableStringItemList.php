<?php

declare(strict_types=1);

namespace Drupal\entity_test\Plugin\Field;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Item list class for computed cacheable string field.
 *
 *  This class sets the cacheable metadata on the field item properties.
 *
 * @see \Drupal\entity_test\Plugin\Field\ComputedTestCacheableIntegerItemList
 */
class ComputedTestCacheableStringItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    /** @var \Drupal\entity_test\Plugin\Field\FieldType\ComputedTestCacheableStringItem $item */
    $item = $this->createItem(0, 'computed test cacheable string field');
    $cacheability = (new CacheableMetadata())
      ->setCacheContexts(['url.query_args:computed_test_cacheable_string_field'])
      ->setCacheTags(['field:computed_test_cacheable_string_field'])
      ->setCacheMaxAge(800);
    $item->get('value')->addCacheableDependency($cacheability);
    $this->list[0] = $item;
  }

}
