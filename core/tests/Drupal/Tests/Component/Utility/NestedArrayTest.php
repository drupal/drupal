<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\NestedArray;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Utility\NestedArray
 * @group Utility
 */
class NestedArrayTest extends TestCase {

  /**
   * Form array to check.
   *
   * @var array
   */
  protected $form;

  /**
   * Array of parents for the nested element.
   *
   * @var array
   */
  protected $parents;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a form structure with a nested element.
    $this->form['details']['element'] = [
     '#value' => 'Nested element',
    ];

    // Set up parent array.
    $this->parents = ['details', 'element'];
  }

  /**
   * Tests getting nested array values.
   *
   * @covers ::getValue
   */
  public function testGetValue() {
    // Verify getting a value of a nested element.
    $value = NestedArray::getValue($this->form, $this->parents);
    $this->assertSame('Nested element', $value['#value'], 'Nested element value found.');

    // Verify changing a value of a nested element by reference.
    $value = &NestedArray::getValue($this->form, $this->parents);
    $value['#value'] = 'New value';
    $value = NestedArray::getValue($this->form, $this->parents);
    $this->assertSame('New value', $value['#value'], 'Nested element value was changed by reference.');
    $this->assertSame('New value', $this->form['details']['element']['#value'], 'Nested element value was changed by reference.');

    // Verify that an existing key is reported back.
    $key_exists = NULL;
    NestedArray::getValue($this->form, $this->parents, $key_exists);
    $this->assertTrue($key_exists, 'Existing key found.');

    // Verify that a non-existing key is reported back and throws no errors.
    $key_exists = NULL;
    $parents = $this->parents;
    $parents[] = 'foo';
    NestedArray::getValue($this->form, $parents, $key_exists);
    $this->assertFalse($key_exists, 'Non-existing key not found.');
  }

  /**
   * Tests setting nested array values.
   *
   * @covers ::setValue
   */
  public function testSetValue() {
    $new_value = [
      '#value' => 'New value',
      '#required' => TRUE,
    ];

    // Verify setting the value of a nested element.
    NestedArray::setValue($this->form, $this->parents, $new_value);
    $this->assertSame('New value', $this->form['details']['element']['#value'], 'Changed nested element value found.');
    $this->assertTrue($this->form['details']['element']['#required'], 'New nested element value found.');
  }

  /**
   * Tests force-setting values.
   *
   * @covers ::setValue
   */
  public function testSetValueForce() {
    $new_value = [
      'one',
    ];
    $this->form['details']['non-array-parent'] = 'string';
    $parents = ['details', 'non-array-parent', 'child'];
    NestedArray::setValue($this->form, $parents, $new_value, TRUE);
    $this->assertSame($new_value, $this->form['details']['non-array-parent']['child'], 'The nested element was not forced to the new value.');
  }

  /**
   * Tests unsetting nested array values.
   *
   * @covers ::unsetValue
   */
  public function testUnsetValue() {
    // Verify unsetting a non-existing nested element throws no errors and the
    // non-existing key is properly reported.
    $key_existed = NULL;
    $parents = $this->parents;
    $parents[] = 'foo';
    NestedArray::unsetValue($this->form, $parents, $key_existed);
    $this->assertTrue(isset($this->form['details']['element']['#value']), 'Outermost nested element key still exists.');
    $this->assertFalse($key_existed, 'Non-existing key not found.');

    // Verify unsetting a nested element.
    $key_existed = NULL;
    NestedArray::unsetValue($this->form, $this->parents, $key_existed);
    $this->assertFalse(isset($this->form['details']['element']), 'Removed nested element not found.');
    $this->assertTrue($key_existed, 'Existing key was found.');
  }

  /**
   * Tests existence of array key.
   */
  public function testKeyExists() {
    // Verify that existing key is found.
    $this->assertTrue(NestedArray::keyExists($this->form, $this->parents), 'Nested key found.');

    // Verify that non-existing keys are not found.
    $parents = $this->parents;
    $parents[] = 'foo';
    $this->assertFalse(NestedArray::keyExists($this->form, $parents), 'Non-existing nested key not found.');
  }

  /**
   * Tests NestedArray::mergeDeepArray().
   *
   * @covers ::mergeDeep
   * @covers ::mergeDeepArray
   */
  public function testMergeDeepArray() {
    $link_options_1 = [
      'fragment' => 'x',
      'attributes' => ['title' => 'X', 'class' => ['a', 'b']],
      'language' => 'en',
    ];
    $link_options_2 = [
      'fragment' => 'y',
      'attributes' => ['title' => 'Y', 'class' => ['c', 'd']],
      'absolute' => TRUE,
    ];
    $expected = [
      'fragment' => 'y',
      'attributes' => ['title' => 'Y', 'class' => ['a', 'b', 'c', 'd']],
      'language' => 'en',
      'absolute' => TRUE,
    ];
    $this->assertSame($expected, NestedArray::mergeDeepArray([$link_options_1, $link_options_2]), 'NestedArray::mergeDeepArray() returned a properly merged array.');
    // Test wrapper function, NestedArray::mergeDeep().
    $this->assertSame($expected, NestedArray::mergeDeep($link_options_1, $link_options_2), 'NestedArray::mergeDeep() returned a properly merged array.');
  }

  /**
   * Tests that arrays with implicit keys are appended, not merged.
   *
   * @covers ::mergeDeepArray
   */
  public function testMergeImplicitKeys() {
    $a = [
      'subkey' => ['X', 'Y'],
    ];
    $b = [
      'subkey' => ['X'],
    ];

    // Drupal core behavior.
    $expected = [
      'subkey' => ['X', 'Y', 'X'],
    ];
    $actual = NestedArray::mergeDeepArray([$a, $b]);
    $this->assertSame($expected, $actual, 'drupal_array_merge_deep() creates new numeric keys in the implicit sequence.');
  }

  /**
   * Tests that even with explicit keys, values are appended, not merged.
   *
   * @covers ::mergeDeepArray
   */
  public function testMergeExplicitKeys() {
    $a = [
      'subkey' => [
        0 => 'A',
        1 => 'B',
      ],
    ];
    $b = [
      'subkey' => [
        0 => 'C',
        1 => 'D',
      ],
    ];

    // Drupal core behavior.
    $expected = [
      'subkey' => [
        0 => 'A',
        1 => 'B',
        2 => 'C',
        3 => 'D',
      ],
    ];
    $actual = NestedArray::mergeDeepArray([$a, $b]);
    $this->assertSame($expected, $actual, 'drupal_array_merge_deep() creates new numeric keys in the explicit sequence.');
  }

  /**
   * Tests that array keys values on the first array are ignored when merging.
   *
   * Even if the initial ordering would place the data from the second array
   * before those in the first one, they are still appended, and the keys on
   * the first array are deleted and regenerated.
   *
   * @covers ::mergeDeepArray
   */
  public function testMergeOutOfSequenceKeys() {
    $a = [
      'subkey' => [
        10 => 'A',
        30 => 'B',
      ],
    ];
    $b = [
      'subkey' => [
        20 => 'C',
        0 => 'D',
      ],
    ];

    // Drupal core behavior.
    $expected = [
      'subkey' => [
        0 => 'A',
        1 => 'B',
        2 => 'C',
        3 => 'D',
      ],
    ];
    $actual = NestedArray::mergeDeepArray([$a, $b]);
    $this->assertSame($expected, $actual, 'drupal_array_merge_deep() ignores numeric key order when merging.');
  }

  /**
   * @covers ::filter
   * @dataProvider providerTestFilter
   */
  public function testFilter($array, $callable, $expected) {
    $this->assertEquals($expected, NestedArray::filter($array, $callable));
  }

  public function providerTestFilter() {
    $data = [];
    $data['1d-array'] = [
      [0, 1, '', TRUE], NULL, [1 => 1, 3 => TRUE]
    ];
    $data['1d-array-callable'] = [
      [0, 1, '', TRUE],
      function ($element) {
        return $element === '';
      },
      [2 => ''],
    ];
    $data['2d-array'] = [
      [[0, 1, '', TRUE], [0, 1, 2, 3]], NULL, [0 => [1 => 1, 3 => TRUE], 1 => [1 => 1, 2 => 2, 3 => 3]],
    ];
    $data['2d-array-callable'] = [
      [[0, 1, '', TRUE], [0, 1, 2, 3]],
      function ($element) {
        return is_array($element) || $element === 3;
      },
      [0 => [], 1 => [3 => 3]],
    ];

    return $data;
  }

}
