<?php

declare(strict_types=1);

namespace Drupal\jsonapi_test_data_type;

/**
 * An object which implements \IteratorAggregate.
 *
 * @implements \IteratorAggregate<int|string, mixed>
 */
class TraversableObject implements \IteratorAggregate {

  /**
   * The test data.
   *
   * @var string
   */
  public $property = "value";

  /**
   * Retrieves the iterator for the object.
   *
   * @return \ArrayIterator<int|string, mixed>
   *   The iterator.
   */
  public function getIterator(): \ArrayIterator {
    return new \ArrayIterator();
  }

}
