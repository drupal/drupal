<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render;

use Drupal\Core\Access\AccessResult;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Render\Element;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element
 * @group Render
 */
class ElementTest extends UnitTestCase {

  /**
   * Tests the property() method.
   */
  public function testProperty(): void {
    $this->assertTrue(Element::property('#property'));
    $this->assertFalse(Element::property('property'));
    $this->assertFalse(Element::property('property#'));
    $this->assertFalse(Element::property(0));
  }

  /**
   * Tests the properties() method.
   */
  public function testProperties(): void {
    $element = [
      '#property1' => 'property1',
      '#property2' => 'property2',
      'property3' => 'property3',
      0 => [],
    ];

    $properties = Element::properties($element);

    $this->assertSame(['#property1', '#property2'], $properties);
  }

  /**
   * Tests the child() method.
   */
  public function testChild(): void {
    $this->assertFalse(Element::child('#property'));
    $this->assertTrue(Element::child('property'));
    $this->assertTrue(Element::child('property#'));
  }

  /**
   * Tests the children() method.
   */
  public function testChildren(): void {
    $element = [
      'child2' => ['#weight' => 10],
      'child1' => ['#weight' => 0],
      'child3' => ['#weight' => 20],
      '#property' => 'property',
    ];

    $expected = ['child2', 'child1', 'child3'];
    $element_copy = $element;
    $this->assertSame($expected, Element::children($element_copy));

    // If #sorted is already set, no sorting should happen.
    $element_copy = $element;
    $element_copy['#sorted'] = TRUE;
    $expected = ['child2', 'child1', 'child3'];
    $this->assertSame($expected, Element::children($element_copy, TRUE));

    // Test with weight sorting, #sorted property should be added.
    $expected = ['child1', 'child2', 'child3'];
    $element_copy = $element;
    $this->assertSame($expected, Element::children($element_copy, TRUE));
    $this->assertArrayHasKey('#sorted', $element_copy);
    $this->assertTrue($element_copy['#sorted']);

    // The order should stay the same if no weights present.
    $element_no_weight = [
      'child2' => [],
      'child1' => [],
      'child3' => [],
      '#property' => 'property',
    ];

    $expected = ['child2', 'child1', 'child3'];
    $this->assertSame($expected, Element::children($element_no_weight, TRUE));

    // The order of children with same weight should be preserved.
    $element_mixed_weight = [
      'child5' => ['#weight' => 10],
      'child3' => ['#weight' => -10],
      'child1' => [],
      'child4' => ['#weight' => 10],
      'child2' => [],
    ];

    $expected = ['child3', 'child1', 'child2', 'child5', 'child4'];
    $this->assertSame($expected, Element::children($element_mixed_weight, TRUE));
  }

  /**
   * Tests the children() method with an invalid key.
   */
  public function testInvalidChildren(): void {
    $element = [
      'foo' => 'bar',
    ];
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('"foo" is an invalid render array key. Value should be an array but got a string.');
    Element::children($element);
  }

  /**
   * Tests the children() method with an ignored key/value pair.
   */
  public function testIgnoredChildren(): void {
    $element = [
      'foo' => NULL,
    ];
    $this->assertSame([], Element::children($element));
  }

  /**
   * Tests the visibleChildren() method.
   *
   * @param array $element
   *   The test element array.
   * @param array $expected_keys
   *   The expected keys to be returned from Element::getVisibleChildren().
   *
   * @dataProvider providerVisibleChildren
   */
  public function testVisibleChildren(array $element, array $expected_keys): void {
    $this->assertSame($expected_keys, Element::getVisibleChildren($element));
  }

  /**
   * Data provider for testVisibleChildren.
   *
   * @return array
   *   An array of test cases.
   */
  public static function providerVisibleChildren() {
    return [
      [['#property1' => '', '#property2' => []], []],
      [['#property1' => '', 'child1' => []], ['child1']],
      [['#property1' => '', 'child1' => [], 'child2' => ['#access' => TRUE]], ['child1', 'child2']],
      [['#property1' => '', 'child1' => [], 'child2' => ['#access' => FALSE]], ['child1']],
      'access_result_object_allowed' => [['#property1' => '', 'child1' => [], 'child2' => ['#access' => AccessResult::allowed()]], ['child1', 'child2']],
      'access_result_object_forbidden' => [['#property1' => '', 'child1' => [], 'child2' => ['#access' => AccessResult::forbidden()]], ['child1']],
      [['#property1' => '', 'child1' => [], 'child2' => ['#type' => 'textfield']], ['child1', 'child2']],
      [['#property1' => '', 'child1' => [], 'child2' => ['#type' => 'value']], ['child1']],
      [['#property1' => '', 'child1' => [], 'child2' => ['#type' => 'hidden']], ['child1']],
    ];
  }

  /**
   * Tests the setAttributes() method.
   *
   * @dataProvider providerTestSetAttributes
   */
  public function testSetAttributes($element, $map, $expected_element): void {
    Element::setAttributes($element, $map);
    $this->assertSame($expected_element, $element);
  }

  /**
   * Data provider for testSetAttributes().
   */
  public static function providerTestSetAttributes() {
    $base = ['#id' => 'id', '#class' => []];
    return [
      [$base, [], $base],
      [$base, ['id', 'class'], $base + ['#attributes' => ['id' => 'id', 'class' => []]]],
      [$base + ['#attributes' => ['id' => 'id-not-overwritten']], ['id', 'class'], $base + ['#attributes' => ['id' => 'id-not-overwritten', 'class' => []]]],
    ];
  }

  /**
   * @covers ::isEmpty
   *
   * @dataProvider providerTestIsEmpty
   */
  public function testIsEmpty(array $element, $expected): void {
    $this->assertSame(Element::isEmpty($element), $expected);
  }

  public static function providerTestIsEmpty() {
    return [
      [[], TRUE],
      [['#attached' => []], FALSE],
      [['#cache' => []], TRUE],
      [['#weight' => []], TRUE],
      // Variations.
      [['#attached' => [], '#cache' => []], FALSE],
      [['#attached' => [], '#weight' => []], FALSE],
      [['#attached' => [], '#weight' => [], '#cache' => []], FALSE],
      [['#cache' => [], '#weight' => []], TRUE],
      [['#cache' => [], '#weight' => [], '#any_other_property' => []], FALSE],
      [
        [
          '#attached' => [],
          '#weight' => [],
          '#cache' => [],
          '#any_other_property' => [],
        ],
        FALSE,
      ],
      // Cover sorting.
      [['#cache' => [], '#weight' => [], '#attached' => []], FALSE],
      [['#cache' => [], '#weight' => []], TRUE],
      [['#weight' => [], '#cache' => []], TRUE],

      [['#cache' => []], TRUE],
      [['#cache' => ['tags' => ['foo']]], TRUE],
      [['#cache' => ['contexts' => ['bar']]], TRUE],

      [['#cache' => [], '#markup' => 'llamas are awesome'], FALSE],
      [['#markup' => 'llamas are the most awesome ever'], FALSE],

      [['#cache' => [], '#any_other_property' => TRUE], FALSE],
      [['#any_other_property' => TRUE], FALSE],
    ];
  }

  /**
   * @covers ::isRenderArray
   * @dataProvider dataProviderIsRenderArray
   */
  public function testIsRenderArray($build, $expected): void {
    $this->assertSame(
      $expected,
      Element::isRenderArray($build)
    );
  }

  public static function dataProviderIsRenderArray() {
    return [
      'valid markup render array' => [['#markup' => 'hello world'], TRUE],
      'invalid "foo" string' => [['foo', '#markup' => 'hello world'], FALSE],
      'null is not an array' => [NULL, FALSE],
      'an empty array is not a render array' => [[], FALSE],
      'funny enough a key with # is valid' => [['#' => TRUE], TRUE],
      'nested arrays can be valid too' => [['one' => [2 => ['#three' => 'charm!']]], TRUE],
    ];
  }

}
