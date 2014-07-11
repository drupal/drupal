<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Render\ElementTest.
 */

namespace Drupal\Tests\Core\Render;

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
  }

  /**
   * Tests the properties() method.
   */
  public function testProperties() {
    $element = array(
      '#property1' => 'property1',
      '#property2' => 'property2',
      'property3' => 'property3'
    );

    $properties = Element::properties($element);

    $this->assertContains('#property1', $properties);
    $this->assertContains('#property2', $properties);
    $this->assertNotContains('property3', $properties);
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
    $element = array(
      'child2' => array('#weight' => 10),
      'child1' => array('#weight' => 0),
      'child3' => array('#weight' => 20),
      '#property' => 'property',
    );

    $expected = array('child2', 'child1', 'child3');
    $element_copy = $element;
    $this->assertSame($expected, Element::children($element_copy));

    // If #sorted is already set, no sorting should happen.
    $element_copy = $element;
    $element_copy['#sorted'] = TRUE;
    $expected = array('child2', 'child1', 'child3');
    $this->assertSame($expected, Element::children($element_copy, TRUE));

    // Test with weight sorting, #sorted property should be added.
    $expected = array('child1', 'child2', 'child3');
    $element_copy = $element;
    $this->assertSame($expected, Element::children($element_copy, TRUE));
    $this->assertArrayHasKey('#sorted', $element_copy);
    $this->assertTrue($element_copy['#sorted']);

    // The order should stay the same if no weights present.
    $element_no_weight = array(
      'child2' => array(),
      'child1' => array(),
      'child3' => array(),
      '#property' => 'property',
    );

    $expected = array('child2', 'child1', 'child3');
    $this->assertSame($expected, Element::children($element_no_weight, TRUE));
  }

  /**
   * Tests the children() method with an invalid key.
   *
   * @expectedException \PHPUnit_Framework_Error
   * @expectedExceptionMessage "foo" is an invalid render array key
   */
  public function testInvalidChildren() {
    $element = array(
      'foo' => 'bar',
    );
    Element::children($element);
  }

  /**
   * Tests the children() method with an ignored key/value pair.
   */
  public function testIgnoredChildren() {
    $element = array(
      'foo' => NULL,
    );
    $this->assertSame(array(), Element::children($element));
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
    return array(
      array(array('#property1' => '', '#property2' => array()), array()),
      array(array('#property1' => '', 'child1' => array()), array('child1')),
      array(array('#property1' => '', 'child1' => array(), 'child2' => array('#access' => TRUE)), array('child1', 'child2')),
      array(array('#property1' => '', 'child1' => array(), 'child2' => array('#access' => FALSE)), array('child1')),
      array(array('#property1' => '', 'child1' => array(), 'child2' => array('#type' => 'textfield')), array('child1', 'child2')),
      array(array('#property1' => '', 'child1' => array(), 'child2' => array('#type' => 'value')), array('child1')),
      array(array('#property1' => '', 'child1' => array(), 'child2' => array('#type' => 'hidden')), array('child1')),
    );
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
    $base = array('#id' => 'id', '#class' => array());
    return array(
      array($base, array(), $base),
      array($base, array('id', 'class'), $base + array('#attributes' => array('id' => 'id', 'class' => array()))),
      array($base + array('#attributes' => array('id' => 'id-not-overwritten')), array('id', 'class'), $base + array('#attributes' => array('id' => 'id-not-overwritten', 'class' => array()))),
    );
  }

}
