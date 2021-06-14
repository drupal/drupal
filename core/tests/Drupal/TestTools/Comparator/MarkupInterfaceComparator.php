<?php

namespace Drupal\TestTools\Comparator;

use Drupal\Component\Render\MarkupInterface;
use SebastianBergmann\Comparator\Comparator;

/**
 * Compares MarkupInterface objects for equality.
 */
class MarkupInterfaceComparator extends Comparator {

  /**
   * {@inheritdoc}
   */
  public function accepts($expected, $actual) {
    // If at least one argument is a MarkupInterface object, we take over and
    // convert to strings before comparing.
    return ($expected instanceof MarkupInterface && $actual instanceof MarkupInterface) ||
      ($expected instanceof MarkupInterface && is_scalar($actual)) ||
      (is_scalar($expected) && $actual instanceof MarkupInterface);
  }

  /**
   * {@inheritdoc}
   */
  public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = FALSE, $ignoreCase = FALSE) {
    $expected_safe = (string) $expected;
    $actual_safe = (string) $actual;
    $comparator = $this->factory->getComparatorFor($expected_safe, $actual_safe);
    $comparator->assertEquals($expected_safe, $actual_safe, $delta, $canonicalize, $ignoreCase);
  }

}
