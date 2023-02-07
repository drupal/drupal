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
    if (is_scalar($expected) && is_scalar($actual)) {
      throw new \LogicException(__METHOD__ . '() should not be called directly. Use TestCase::assertEquals() instead');
    }
    $expected_safe = (string) $expected;
    $actual_safe = (string) $actual;
    $expected_safe_stripped = strip_tags($expected_safe);
    $actual_safe_stripped = strip_tags($actual_safe);
    if (!($expected instanceof MarkupInterface && $actual instanceof MarkupInterface)) {
      if ($expected_safe !== $expected_safe_stripped && $actual_safe !== $actual_safe_stripped) {
        @trigger_error("Using assert[Not]Equals() to compare markup between MarkupInterface objects and plain strings is deprecated in drupal:10.1.0 and will throw an error from drupal:11.0.0. Expected: '{$expected_safe}' - Actual '{$actual_safe}'. Use assert[Not]Same() and cast objects to string instead. See https://www.drupal.org/node/3334057", E_USER_DEPRECATED);
      }
    }
    $comparator = $this->factory->getComparatorFor($expected_safe_stripped, $actual_safe_stripped);
    $comparator->assertEquals($expected_safe_stripped, $actual_safe_stripped, $delta, $canonicalize, $ignoreCase);
  }

}
