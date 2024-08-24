<?php

declare(strict_types=1);

namespace Drupal\entity_test\TypedData;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for test strings.
 */
class ComputedString extends TypedData implements CacheableDependencyInterface {

  /**
   * The data value.
   *
   * @var mixed
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    /** @var \Drupal\Core\Field\FieldItemInterface $item */
    $item = $this->getParent();
    $computed_value = "Computed! " . $item->get('value')->getString();

    return $computed_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCastedValue() {
    return $this->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return ['you_are_it', 'no_tag_backs'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['request_format'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
