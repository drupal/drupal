<?php

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
  public function testProperty() {
    $this->assertTrue(Element::property('#property'));
    $this->assertFalse(Element::property('property'));
    $this->assertFalse(Element::property('property#'));
    $this->assertFalse(Element::property(0));
  }

  /**
   * Tests the properties() method.
   */
  public function testProperties() {
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
  public function testChild() {
    $this->assertFalse(Element::child('#property'));
    $this->assertTrue(Element::child('property'));
    $this->assertTrue(Element::child('property#'));
  }

  /**
   * Tests the children() method.
   */
  public function testChildren() {
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
  public function testInvalidChildren() {
    $element = [
      'foo' => 'bar',
    ];
    $this->expectError();
    $this->expectErrorMessage('"foo" is an invalid render array key');
    Element::children($element);
  }

  /**
   * Tests the children() method with an ignored key/value pair.
   */
  public function testIgnoredChildren() {
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
  public function testVisibleChildren(array $element, array $expected_keys) {
    $this->assertSame($expected_keys, Element::getVisibleChildren($element));
  }

  /**
   * Data provider for testVisibleChildren.
   *
   * @return array
   */
  public function providerVisibleChildren() {
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
  public function testSetAttributes($element, $map, $expected_element) {
    Element::setAttributes($element, $map);
    $this->assertSame($expected_element, $element);
  }

  /**
   * Data provider for testSetAttributes().
   */
  public function providerTestSetAttributes() {
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
  public function testIsEmpty(array $element, $expected) {
    $this->assertSame(Element::isEmpty($element), $expected);
  }

  public function providerTestIsEmpty() {
    return [
      [[], TRUE],
      [['#cache' => []], TRUE],
      [['#cache' => ['tags' => ['foo']]], TRUE],
      [['#cache' => ['contexts' => ['bar']]], TRUE],

      [['#cache' => [], '#markup' => 'llamas are awesome'], FALSE],
      [['#markup' => 'llamas are the most awesome ever'], FALSE],

      [['#cache' => [], '#any_other_property' => TRUE], FALSE],
      [['#any_other_property' => TRUE], FALSE],
    ];
  }

}
