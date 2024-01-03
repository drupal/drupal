<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormStateValuesTrait;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormStateValuesTrait
 *
 * @group Form
 */
class FormStateValuesTraitTest extends UnitTestCase {

  /**
   * Tests that setting the value for an element adds to the values.
   *
   * @covers ::setValueForElement
   */
  public function testSetValueForElement() {
    $element = [
      '#parents' => [
        'foo',
        'bar',
      ],
    ];
    $value = $this->randomMachineName();

    $form_state = new FormStateValuesTraitStub();
    $form_state->setValueForElement($element, $value);
    $expected = [
      'foo' => [
        'bar' => $value,
      ],
    ];
    $this->assertSame($expected, $form_state->getValues());
  }

  /**
   * @covers ::getValue
   *
   * @dataProvider providerGetValue
   */
  public function testGetValue($key, $expected, $default = NULL) {
    $form_state = (new FormStateValuesTraitStub())->setValues([
      'foo' => 'one',
      'bar' => [
        'baz' => 'two',
      ],
    ]);
    $this->assertSame($expected, $form_state->getValue($key, $default));
  }

  /**
   * Provides data to self::testGetValue().
   *
   * @return array[]
   *   Items are arrays of two items:
   *   - The key for which to get the value (string)
   *   - The expected value (mixed).
   *   - The default value (mixed).
   */
  public function providerGetValue() {
    $data = [];
    $data[] = [
      'foo', 'one',
    ];
    $data[] = [
      ['bar', 'baz'], 'two',
    ];
    $data[] = [
      ['foo', 'bar', 'baz'], NULL,
    ];
    $data[] = [
      'baz', 'baz', 'baz',
    ];
    $data[] = [
      NULL,
      [
        'foo' => 'one',
        'bar' => [
          'baz' => 'two',
        ],
      ],
    ];
    return $data;
  }

  /**
   * @covers ::getValue
   */
  public function testGetValueModifyReturn() {
    $initial_values = $values = [
      'foo' => 'one',
      'bar' => [
        'baz' => 'two',
      ],
    ];
    $form_state = (new FormStateValuesTraitStub())->setValues($values);

    $value = &$form_state->getValue(NULL);
    $this->assertSame($initial_values, $value);
    $value = ['bing' => 'bang'];
    $this->assertSame(['bing' => 'bang'], $form_state->getValues());
    $this->assertSame('bang', $form_state->getValue('bing'));
    $this->assertSame(['bing' => 'bang'], $form_state->getValue(NULL));
  }

  /**
   * @covers ::setValue
   *
   * @dataProvider providerSetValue
   */
  public function testSetValue($key, $value, $expected) {
    $form_state = (new FormStateValuesTraitStub())->setValues([
      'bar' => 'wrong',
    ]);
    $form_state->setValue($key, $value);
    $this->assertSame($expected, $form_state->getValues());
  }

  /**
   * Provides data to self::testSetValue().
   *
   * @return array[]
   *   Items are arrays of two items:
   *   - The key for which to set a new value (string)
   *   - The new value to set (mixed).
   *   - The expected form state values after setting the new value (mixed[]).
   */
  public function providerSetValue() {
    $data = [];
    $data[] = [
      'foo', 'one', ['bar' => 'wrong', 'foo' => 'one'],
    ];
    $data[] = [
      ['bar', 'baz'], 'two', ['bar' => ['baz' => 'two']],
    ];
    $data[] = [
      ['foo', 'bar', 'baz'], NULL, ['bar' => 'wrong', 'foo' => ['bar' => ['baz' => NULL]]],
    ];
    return $data;
  }

  /**
   * @covers ::hasValue
   *
   * @dataProvider providerHasValue
   */
  public function testHasValue($key, $expected) {
    $form_state = (new FormStateValuesTraitStub())->setValues([
      'foo' => 'one',
      'bar' => [
        'baz' => 'two',
      ],
      'true' => TRUE,
      'false' => FALSE,
      'null' => NULL,
    ]);
    $this->assertSame($expected, $form_state->hasValue($key));
  }

  /**
   * Provides data to self::testHasValue().
   *
   * @return array[]
   *   Items are arrays of two items:
   *   - The key to check for in the form state (string)
   *   - Whether the form state has an item with that key (bool).
   */
  public function providerHasValue() {
    $data = [];
    $data[] = [
      'foo', TRUE,
    ];
    $data[] = [
      ['bar', 'baz'], TRUE,
    ];
    $data[] = [
      ['foo', 'bar', 'baz'], FALSE,
    ];
    $data[] = [
      'true', TRUE,
    ];
    $data[] = [
      'false', TRUE,
    ];
    $data[] = [
      'null', FALSE,
    ];
    return $data;
  }

  /**
   * @covers ::isValueEmpty
   *
   * @dataProvider providerIsValueEmpty
   */
  public function testIsValueEmpty($key, $expected) {
    $form_state = (new FormStateValuesTraitStub())->setValues([
      'foo' => 'one',
      'bar' => [
        'baz' => 'two',
      ],
      'true' => TRUE,
      'false' => FALSE,
      'null' => NULL,
    ]);
    $this->assertSame($expected, $form_state->isValueEmpty($key));
  }

  /**
   * Provides data to self::testIsValueEmpty().
   *
   * @return array[]
   *   Items are arrays of two items:
   *   - The key to check for in the form state (string)
   *   - Whether the value is empty or not (bool).
   */
  public function providerIsValueEmpty() {
    $data = [];
    $data[] = [
      'foo', FALSE,
    ];
    $data[] = [
      ['bar', 'baz'], FALSE,
    ];
    $data[] = [
      ['foo', 'bar', 'baz'], TRUE,
    ];
    $data[] = [
      'true', FALSE,
    ];
    $data[] = [
      'false', TRUE,
    ];
    $data[] = [
      'null', TRUE,
    ];
    return $data;
  }

}

class FormStateValuesTraitStub {

  use FormStateValuesTrait;

  /**
   * The submitted form values.
   *
   * @var mixed[]
   */
  protected $values = [];

  /**
   * {@inheritdoc}
   */
  public function &getValues() {
    return $this->values;
  }

}
