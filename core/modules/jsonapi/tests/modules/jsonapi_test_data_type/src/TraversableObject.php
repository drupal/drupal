<?php

namespace Drupal\jsonapi_test_data_type;

/**
 * An object which implements \IteratorAggregate.
 */
class TraversableObject implements \IteratorAggregate {

  public $property = "value";

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function getIterator() {
    return new \ArrayIterator();
  }

}
