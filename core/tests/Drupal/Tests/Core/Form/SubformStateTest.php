<?php

namespace Drupal\Tests\Core\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Form\SubformStateInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Form\SubformState
 *
 * @group Form
 */
class SubformStateTest extends UnitTestCase {

  /**
   * The form state's values test fixture.
   *
   * @var mixed[]
   */
  protected $formStateValues = [
    'foo' => 'bar',
    'dog' => [
      'breed' => 'Pit bull',
      'name' => 'Dodger',
    ],
  ];

  /**
   * The parent form.
   *
   * @var mixed[]
   */
  protected $parentForm = [
    '#parents' => [],
    'foo' => [
      '#parents' => ['foo'],
      '#array_parents' => ['foo'],
    ],
    'dog' => [
      '#parents' => ['dog'],
      '#array_parents' => ['dog'],
      'breed' => [
        '#parents' => ['dog', 'breed'],
        '#array_parents' => ['dog', 'breed'],
      ],
      'name' => [
        '#parents' => ['dog', 'name'],
        '#array_parents' => ['dog', 'name'],
      ],
    ],
  ];

  /**
   * @covers ::getValues
   * @covers ::getParents
   *
   * @dataProvider providerGetValues
   *
   * @param string[] $parents
   *   The parents.
   * @param string $expected
   *   The expected state values.
   */
  public function testGetValues(array $parents, $expected) {
    $parent_form_state = new FormState();
    $parent_form_state->setValues($this->formStateValues);

    $subform = NestedArray::getValue($this->parentForm, $parents);
    $subform_state = SubformState::createForSubform($subform, $this->parentForm, $parent_form_state);
    $subform_state_values = &$subform_state->getValues();
    $this->assertSame($expected, $subform_state_values);

    // Modify the retrieved values and confirm they are modified by reference in
    // the parent form state.
    $subform_state_values['fish'] = 'Jim';
    $this->assertSame($subform_state_values, $subform_state->getValues());
  }

  /**
   * Provides data to self::testGetValues().
   */
  public function providerGetValues() {
    $data = [];
    $data['exist'] = [
      ['dog'],
      $this->formStateValues['dog'],
    ];

    return $data;
  }

  /**
   * @covers ::getValues
   * @covers ::getParents
   *
   * @dataProvider providerGetValuesBroken
   *
   * @param string[] $parents
   *   The parents.
   * @param string $expected
   *   The expected state values.
   */
  public function testGetValuesBroken(array $parents, $expected) {
    $this->expectException(\UnexpectedValueException::class);
    $this->testGetValues($parents, $expected);
  }

  /**
   * Provides data to self::testGetValuesBroken().
   */
  public function providerGetValuesBroken() {
    $data = [];
    $data['exist'] = [
      ['foo'],
      $this->formStateValues['foo'],
    ];
    $data['nested'] = [
      ['dog', 'name'],
      'Dodger',
    ];

    return $data;
  }

  /**
   * @covers ::getValue
   *
   * @dataProvider providerTestGetValue
   */
  public function testGetValue($parents, $key, $expected, $default = NULL) {
    $parent_form_state = new FormState();
    $parent_form_state->setValues($this->formStateValues);

    $subform = NestedArray::getValue($this->parentForm, $parents);
    $subform_state = SubformState::createForSubform($subform, $this->parentForm, $parent_form_state);
    $subform_state_value = &$subform_state->getValue($key, $default);
    $this->assertSame($expected, $subform_state_value);

    // Modify the retrieved values and confirm they are modified by reference in
    // the parent form state.
    $subform_state_value = 'Jim';
    $this->assertSame($subform_state_value, $subform_state->getValue($key));
  }

  /**
   * Provides data to self::testGetValue().
   */
  public function providerTestGetValue() {
    $data = [];
    $data['exist'] = [
      ['dog'],
      'name',
      'Dodger',
    ];

    return $data;
  }

  /**
   * @covers ::getValue
   *
   * @dataProvider providerTestGetValueBroken
   */
  public function testGetValueBroken(array $parents, $key, $expected, $default = NULL) {
    $this->expectException(\UnexpectedValueException::class);
    $this->testGetValue($parents, $key, $expected, $default);
  }

  /**
   * Provides data to self::testGetValueBroken().
   */
  public function providerTestGetValueBroken() {
    $data = [];
    $data['nested'] = [
      ['dog', 'name'],
      NULL,
      'Dodger',
    ];

    return $data;
  }

  /**
   * @covers ::setValues
   *
   * @dataProvider providerTestSetValues
   */
  public function testSetValues($parents, $new_values, $expected) {
    $parent_form_state = new FormState();
    $parent_form_state->setValues($this->formStateValues);

    $subform = NestedArray::getValue($this->parentForm, $parents);
    $subform_state = SubformState::createForSubform($subform, $this->parentForm, $parent_form_state);
    $this->assertSame($subform_state, $subform_state->setValues($new_values));
    $this->assertSame($expected, $parent_form_state->getValues());
  }

  /**
   * Provides data to self::testSetValues().
   */
  public function providerTestSetValues() {
    $data = [];
    $data['exist'] = [
      ['dog'],
      [],
      [
        'foo' => 'bar',
        'dog' => [],
      ],
    ];
    return $data;
  }

  /**
   * @covers ::setValues
   *
   * @dataProvider providerTestSetValuesBroken
   */
  public function testSetValuesBroken($parents, $new_values, $expected) {
    $this->expectException(\UnexpectedValueException::class);
    $this->testSetValues($parents, $new_values, $expected);
  }

  /**
   * Provides data to self::testSetValuesBroken().
   */
  public function providerTestSetValuesBroken() {
    $data = [];
    $data['exist'] = [
      ['foo'],
      [],
      [
        'foo' => [],
        'dog' => $this->formStateValues['dog'],
      ],
    ];
    return $data;
  }

  /**
   * @covers ::getCompleteFormState
   */
  public function testGetCompleteFormStateWithParentCompleteForm() {
    $parent_form_state = $this->prophesize(FormStateInterface::class);
    $subform_state = SubformState::createForSubform($this->parentForm['dog'], $this->parentForm, $parent_form_state->reveal());
    $this->assertSame($parent_form_state->reveal(), $subform_state->getCompleteFormState());
  }

  /**
   * @covers ::getCompleteFormState
   */
  public function testGetCompleteFormStateWithParentSubform() {
    $complete_form_state = $this->prophesize(FormStateInterface::class);
    $parent_form_state = $this->prophesize(SubformStateInterface::class);
    $parent_form_state->getCompleteFormState()
      ->willReturn($complete_form_state->reveal())
      ->shouldBeCalled();
    $subform_state = SubformState::createForSubform($this->parentForm['dog'], $this->parentForm, $parent_form_state->reveal());
    $this->assertSame($complete_form_state->reveal(), $subform_state->getCompleteFormState());
  }

  /**
   * @covers ::setLimitValidationErrors
   */
  public function testSetLimitValidationErrors() {
    $parent_limit_validation_errors = ['dog', 'name'];
    $limit_validation_errors = ['name'];

    $parent_form_state = $this->prophesize(FormStateInterface::class);
    $parent_form_state->setLimitValidationErrors($parent_limit_validation_errors)
      ->shouldBeCalled();

    $subform_state = SubformState::createForSubform($this->parentForm['dog'], $this->parentForm, $parent_form_state->reveal());
    $this->assertSame($subform_state, $subform_state->setLimitValidationErrors($limit_validation_errors));
  }

  /**
   * @covers ::getLimitValidationErrors
   */
  public function testGetLimitValidationErrors() {
    $parent_limit_validation_errors = ['dog', 'name'];
    $limit_validation_errors = ['name'];

    $parent_form_state = $this->prophesize(FormStateInterface::class);
    $parent_form_state->getLimitValidationErrors()
      ->willReturn($parent_limit_validation_errors)
      ->shouldBeCalled();

    $subform_state = SubformState::createForSubform($this->parentForm['dog'], $this->parentForm, $parent_form_state->reveal());
    $this->assertSame($limit_validation_errors, $subform_state->getLimitValidationErrors());
  }

  /**
   * @covers ::setErrorByName
   */
  public function testSetErrorByName() {
    $parent_form_error_name = 'dog][name';
    $subform_error_name = 'name';
    // cSpell:disable-next-line
    $message = 'De kat krabt de krullen van de trap.';

    $parent_form_state = $this->prophesize(FormStateInterface::class);
    $parent_form_state->setErrorByName($parent_form_error_name, $message)
      ->shouldBeCalled();

    $subform_state = SubformState::createForSubform($this->parentForm['dog'], $this->parentForm, $parent_form_state->reveal());
    $this->assertSame($subform_state, $subform_state->setErrorByName($subform_error_name, $message));
  }

}
