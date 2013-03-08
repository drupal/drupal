<?php

/**
 * @file
 * Contains \Drupal\Core\NestedArrayUnitTest.
 */

namespace Drupal\Tests\Core;

use Drupal\Component\Utility\NestedArray;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the NestedArray helper class.
 *
 * @group System
 */
class NestedArrayUnitTest extends UnitTestCase {

  /**
   * Form array to check.
   */
  protected $form;

  /**
   * Array of parents for the nested element.
   */
  protected $parents;

  public static function getInfo() {
    return array(
      'name' => 'NestedArray functionality',
      'description' => 'Tests the NestedArray helper class.',
      'group' => 'System',
    );
  }

  function setUp() {
    parent::setUp();

    // Create a form structure with a nested element.
    $this->form['details']['element'] = array(
     '#value' => 'Nested element',
    );

    // Set up parent array.
    $this->parents = array('details', 'element');
  }

  /**
   * Tests getting nested array values.
   */
  function testGetValue() {
    // Verify getting a value of a nested element.
    $value = NestedArray::getValue($this->form, $this->parents);
    $this->assertEquals($value['#value'], 'Nested element', 'Nested element value found.');

    // Verify changing a value of a nested element by reference.
    $value = &NestedArray::getValue($this->form, $this->parents);
    $value['#value'] = 'New value';
    $value = NestedArray::getValue($this->form, $this->parents);
    $this->assertEquals($value['#value'], 'New value', 'Nested element value was changed by reference.');
    $this->assertEquals($this->form['details']['element']['#value'], 'New value', 'Nested element value was changed by reference.');

    // Verify that an existing key is reported back.
    $key_exists = NULL;
    NestedArray::getValue($this->form, $this->parents, $key_exists);
    $this->assertSame($key_exists, TRUE, 'Existing key found.');

    // Verify that a non-existing key is reported back and throws no errors.
    $key_exists = NULL;
    $parents = $this->parents;
    $parents[] = 'foo';
    NestedArray::getValue($this->form, $parents, $key_exists);
    $this->assertSame($key_exists, FALSE, 'Non-existing key not found.');
  }

  /**
   * Tests setting nested array values.
   */
  function testSetValue() {
    $new_value = array(
      '#value' => 'New value',
      '#required' => TRUE,
    );

    // Verify setting the value of a nested element.
    NestedArray::setValue($this->form, $this->parents, $new_value);
    $this->assertEquals($this->form['details']['element']['#value'], 'New value', 'Changed nested element value found.');
    $this->assertSame($this->form['details']['element']['#required'], TRUE, 'New nested element value found.');
  }

  /**
   * Tests unsetting nested array values.
   */
  function testUnsetValue() {
    // Verify unsetting a non-existing nested element throws no errors and the
    // non-existing key is properly reported.
    $key_existed = NULL;
    $parents = $this->parents;
    $parents[] = 'foo';
    NestedArray::unsetValue($this->form, $parents, $key_existed);
    $this->assertTrue(isset($this->form['details']['element']['#value']), 'Outermost nested element key still exists.');
    $this->assertSame($key_existed, FALSE, 'Non-existing key not found.');

    // Verify unsetting a nested element.
    $key_existed = NULL;
    NestedArray::unsetValue($this->form, $this->parents, $key_existed);
    $this->assertFalse(isset($this->form['details']['element']), 'Removed nested element not found.');
    $this->assertSame($key_existed, TRUE, 'Existing key was found.');
  }

  /**
   * Tests existence of array key.
   */
  function testKeyExists() {
    // Verify that existing key is found.
    $this->assertSame(NestedArray::keyExists($this->form, $this->parents), TRUE, 'Nested key found.');

    // Verify that non-existing keys are not found.
    $parents = $this->parents;
    $parents[] = 'foo';
    $this->assertSame(NestedArray::keyExists($this->form, $parents), FALSE, 'Non-existing nested key not found.');
  }

  /**
   * Tests NestedArray::mergeDeepArray().
   */
  function testMergeDeepArray() {
    $link_options_1 = array(
      'fragment' => 'x',
      'attributes' => array('title' => 'X', 'class' => array('a', 'b')),
      'language' => 'en',
    );
    $link_options_2 = array(
      'fragment' => 'y',
      'attributes' => array('title' => 'Y', 'class' => array('c', 'd')),
      'html' => TRUE,
    );
    $expected = array(
      'fragment' => 'y',
      'attributes' => array('title' => 'Y', 'class' => array('a', 'b', 'c', 'd')),
      'language' => 'en',
      'html' => TRUE,
    );
    $this->assertSame(NestedArray::mergeDeepArray(array($link_options_1, $link_options_2)), $expected, 'NestedArray::mergeDeepArray() returned a properly merged array.');
  }
}
