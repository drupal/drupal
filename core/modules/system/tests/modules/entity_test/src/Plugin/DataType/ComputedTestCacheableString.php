<?php

namespace Drupal\entity_test\Plugin\DataType;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * The string data type with cacheability metadata.
 *
 * The plain value of a string is a regular PHP string. For setting the value
 * any PHP variable that casts to a string may be passed.
 */
#[DataType(
  id: "computed_test_cacheable_string",
  label: new TranslatableMarkup("Computed Test Cacheable String"),
)]
class ComputedTestCacheableString extends StringData implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

}
