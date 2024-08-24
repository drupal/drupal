<?php

declare(strict_types=1);

namespace Drupal\entity_test\Plugin\Field;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Item list class for computed cacheable string field.
 *
 * This class sets the cacheable metadata on the field item list directly.
 *
 * @see \Drupal\entity_test\Plugin\Field\ComputedTestCacheableStringItemList
 */
class ComputedTestCacheableIntegerItemList extends FieldItemList implements CacheableDependencyInterface {

  use CacheableDependencyTrait, ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $value = \Drupal::state()->get('entity_test_computed_integer_value', 0);
    $item = $this->createItem(0, $value);
    $cacheability = (new CacheableMetadata())
      ->setCacheContexts(['url.query_args:computed_test_cacheable_integer_field'])
      ->setCacheTags(['field:computed_test_cacheable_integer_field'])
      ->setCacheMaxAge(31536000);
    $this->setCacheability($cacheability);
    $this->list[0] = $item;
  }

}
