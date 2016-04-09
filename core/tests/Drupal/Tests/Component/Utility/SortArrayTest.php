<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Tests\UnitTestCase;
use Drupal\Component\Utility\SortArray;

/**
 * Tests the SortArray component.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\SortArray
 */
class SortArrayTest extends UnitTestCase {

  /**
   * Tests SortArray::sortByWeightElement() input against expected output.
   *
   * @dataProvider providerSortByWeightElement
   * @covers ::sortByWeightElement
   * @covers ::sortByKeyInt
   *
   * @param array $a
   *   The first input array for the SortArray::sortByWeightElement() method.
   * @param array $b
   *   The second input array for the SortArray::sortByWeightElement().
   * @param int $expected
   *   The expected output from calling the method.
   */
  public function testSortByWeightElement($a, $b, $expected) {
    $result = SortArray::sortByWeightElement($a, $b);
    $this->assertBothNegativePositiveOrZero($expected, $result);
  }

  /**
   * Data provider for SortArray::sortByWeightElement().
   *
   * @return array
   *   An array of tests, matching the parameter inputs for
   *   testSortByWeightElement.
   *
   * @see \Drupal\Tests\Component\Utility\SortArrayTest::testSortByWeightElement()
   */
  public function providerSortByWeightElement() {
    $tests = array();

    // Weights set and equal.
    $tests[] = array(
      array('weight' => 1),
      array('weight' => 1),
      0
    );

    // Weights set and $a is less (lighter) than $b.
    $tests[] = array(
      array('weight' => 1),
      array('weight' => 2),
      -1
    );

    // Weights set and $a is greater (heavier) than $b.
    $tests[] = array(
      array('weight' => 2),
      array('weight' => 1),
      1
    );

    // Weights not set.
    $tests[] = array(
      array(),
      array(),
      0
    );

    // Weights for $b not set.
    $tests[] = array(
      array('weight' => 1),
      array(),
      1
    );

    // Weights for $a not set.
    $tests[] = array(
      array(),
      array('weight' => 1),
      -1
    );

    return $tests;
  }

  /**
   * Tests SortArray::sortByWeightProperty() input against expected output.
   *
   * @dataProvider providerSortByWeightProperty
   * @covers ::sortByWeightProperty
   * @covers ::sortByKeyInt
   *
   * @param array $a
   *   The first input array for the SortArray::sortByWeightProperty() method.
   * @param array $b
   *   The second input array for the SortArray::sortByWeightProperty().
   * @param int $expected
   *   The expected output from calling the method.
   */
  public function testSortByWeightProperty($a, $b, $expected) {
    $result = SortArray::sortByWeightProperty($a, $b);
    $this->assertBothNegativePositiveOrZero($expected, $result);
  }

  /**
   * Data provider for SortArray::sortByWeightProperty().
   *
   * @return array
   *   An array of tests, matching the parameter inputs for
   *   testSortByWeightProperty.
   *
   * @see \Drupal\Tests\Component\Utility\SortArrayTest::testSortByWeightProperty()
   */
  public function providerSortByWeightProperty() {
    $tests = array();

    // Weights set and equal.
    $tests[] = array(
      array('#weight' => 1),
      array('#weight' => 1),
      0
    );

    // Weights set and $a is less (lighter) than $b.
    $tests[] = array(
      array('#weight' => 1),
      array('#weight' => 2),
      -1
    );

    // Weights set and $a is greater (heavier) than $b.
    $tests[] = array(
      array('#weight' => 2),
      array('#weight' => 1),
      1
    );

    // Weights not set.
    $tests[] = array(
      array(),
      array(),
      0
    );

    // Weights for $b not set.
    $tests[] = array(
      array('#weight' => 1),
      array(),
      1
    );

    // Weights for $a not set.
    $tests[] = array(
      array(),
      array('#weight' => 1),
      -1
    );

    return $tests;
  }

  /**
   * Tests SortArray::sortByTitleElement() input against expected output.
   *
   * @dataProvider providerSortByTitleElement
   * @covers ::sortByTitleElement
   * @covers ::sortByKeyString
   *
   * @param array $a
   *   The first input item for comparison.
   * @param array $b
   *   The second item for comparison.
   * @param int $expected
   *   The expected output from calling the method.
   */
  public function testSortByTitleElement($a, $b, $expected) {
    $result = SortArray::sortByTitleElement($a, $b);
    $this->assertBothNegativePositiveOrZero($expected, $result);
  }

  /**
   * Data provider for SortArray::sortByTitleElement().
   *
   * @return array
   *   An array of tests, matching the parameter inputs for
   *   testSortByTitleElement.
   *
   * @see \Drupal\Tests\Component\Utility\SortArrayTest::testSortByTitleElement()
   */
  public function providerSortByTitleElement() {
    $tests = array();

    // Titles set and equal.
    $tests[] = array(
      array('title' => 'test'),
      array('title' => 'test'),
      0
    );

    // Title $a not set.
    $tests[] = array(
      array(),
      array('title' => 'test'),
      -4
    );

    // Title $b not set.
    $tests[] = array(
      array('title' => 'test'),
      array(),
      4
    );

    // Titles set but not equal.
    $tests[] = array(
      array('title' => 'test'),
      array('title' => 'testing'),
      -1
    );

    // Titles set but not equal.
    $tests[] = array(
      array('title' => 'testing'),
      array('title' => 'test'),
      1
    );

    return $tests;
  }

  /**
   * Tests SortArray::sortByTitleProperty() input against expected output.
   *
   * @dataProvider providerSortByTitleProperty
   * @covers ::sortByTitleProperty
   * @covers ::sortByKeyString
   *
   * @param array $a
   *   The first input item for comparison.
   * @param array $b
   *   The second item for comparison.
   * @param int $expected
   *   The expected output from calling the method.
   */
  public function testSortByTitleProperty($a, $b, $expected) {
    $result = SortArray::sortByTitleProperty($a, $b);
    $this->assertBothNegativePositiveOrZero($expected, $result);
  }

  /**
   * Data provider for SortArray::sortByTitleProperty().
   *
   * @return array
   *   An array of tests, matching the parameter inputs for
   *   testSortByTitleProperty.
   *
   * @see \Drupal\Tests\Component\Utility\SortArrayTest::testSortByTitleProperty()
   */
  public function providerSortByTitleProperty() {
    $tests = array();

    // Titles set and equal.
    $tests[] = array(
      array('#title' => 'test'),
      array('#title' => 'test'),
      0
    );

    // Title $a not set.
    $tests[] = array(
      array(),
      array('#title' => 'test'),
      -4
    );

    // Title $b not set.
    $tests[] = array(
      array('#title' => 'test'),
      array(),
      4
    );

    // Titles set but not equal.
    $tests[] = array(
      array('#title' => 'test'),
      array('#title' => 'testing'),
      -1
    );

    // Titles set but not equal.
    $tests[] = array(
      array('#title' => 'testing'),
      array('#title' => 'test'),
      1
    );

    return $tests;
  }

  /**
   * Asserts that numbers are either both negative, both positive or both zero.
   *
   * The exact values returned by comparison functions differ between PHP
   * versions and are considered an "implementation detail".
   *
   * @param int $expected
   *   Expected comparison function return value.
   * @param int $result
   *   Actual comparison function return value.
   */
  protected function assertBothNegativePositiveOrZero($expected, $result) {
    $this->assertTrue(is_numeric($expected) && is_numeric($result), 'Parameters are numeric.');
    $this->assertTrue(($expected < 0 && $result < 0) || ($expected > 0 && $result > 0) || ($expected === 0 && $result === 0), 'Numbers are either both negative, both positive or both zero.');
  }

}
