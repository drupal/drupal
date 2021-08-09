<?php

namespace Drupal\KernelTests\Core\TypedData;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Tests AllowedValues validation constraint with both valid and invalid values.
 *
 * @group Validation
 */
class AllowedValuesConstraintValidatorTest extends KernelTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedData;

  protected function setUp(): void {
    parent::setUp();
    $this->typedData = $this->container->get('typed_data_manager');
  }

  /**
   * Tests the AllowedValues validation constraint validator.
   *
   * For testing we define an integer with a set of allowed values.
   */
  public function testValidation() {
    // Create a definition that specifies some AllowedValues.
    $definition = DataDefinition::create('integer')
      ->addConstraint('AllowedValues', [1, 2, 3]);

    // Test the validation.
    $typed_data = $this->typedData->create($definition, 1);
    $violations = $typed_data->validate();
    $this->assertEquals(0, $violations->count(), 'Validation passed for correct value.');

    // Test the validation when an invalid value is passed.
    $typed_data = $this->typedData->create($definition, 4);
    $violations = $typed_data->validate();
    $this->assertEquals(1, $violations->count(), 'Validation failed for incorrect value.');

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEquals('The value you selected is not a valid choice.', $violation->getMessage(), 'The message for invalid value is correct.');
    $this->assertEquals($typed_data, $violation->getRoot(), 'Violation root is correct.');
    $this->assertEquals(4, $violation->getInvalidValue(), 'The invalid value is set correctly in the violation.');

    // Test the validation when a value of an incorrect type is passed.
    $typed_data = $this->typedData->create($definition, '1');
    $violations = $typed_data->validate();
    $this->assertEquals(0, $violations->count(), 'Value is coerced to the correct type and is valid.');
  }

  /**
   * Tests the AllowedValuesConstraintValidator with callbacks.
   */
  public function testValidationCallback() {
    // Create a definition that specifies some AllowedValues and a callback.
    // This tests that callbacks have a higher priority than a supplied list of
    // values and can be used to coerce the value to the correct type.
    $definition = DataDefinition::create('string')
      ->addConstraint('AllowedValues', ['choices' => [1, 2, 3], 'callback' => [static::class, 'allowedValueCallback']]);
    $typed_data = $this->typedData->create($definition, 'a');
    $violations = $typed_data->validate();
    $this->assertEquals(0, $violations->count(), 'Validation passed for correct value.');

    $typed_data = $this->typedData->create($definition, 1);
    $violations = $typed_data->validate();
    $this->assertEquals(0, $violations->count(), 'Validation passed for value that will be cast to the correct type.');

    $typed_data = $this->typedData->create($definition, 2);
    $violations = $typed_data->validate();
    $this->assertEquals(1, $violations->count(), 'Validation failed for incorrect value.');

    $typed_data = $this->typedData->create($definition, 'd');
    $violations = $typed_data->validate();
    $this->assertEquals(1, $violations->count(), 'Validation failed for incorrect value.');

    $typed_data = $this->typedData->create($definition, 0);
    $violations = $typed_data->validate();
    $this->assertEquals(1, $violations->count(), 'Validation failed for incorrect value.');
  }

  /**
   * An AllowedValueConstraint callback.
   *
   * @return string[]
   *   A list of allowed values.
   */
  public static function allowedValueCallback() {
    return ['a', 'b', 'c', '1'];
  }

  /**
   * Tests the AllowedValuesConstraintValidator with an invalid callback.
   */
  public function testValidationCallbackException() {
    // Create a definition that specifies some AllowedValues and a callback.
    // This tests that callbacks have a higher priority than a supplied list of
    // values and can be used to coerce the value to the correct type.
    $definition = DataDefinition::create('string')
      ->addConstraint('AllowedValues', ['choices' => [1, 2, 3], 'callback' => [static::class, 'doesNotExist']]);
    $typed_data = $this->typedData->create($definition, 1);

    $this->expectException(ConstraintDefinitionException::class);
    $this->expectExceptionMessage('The AllowedValuesConstraint constraint expects a valid callback');
    $typed_data->validate();
  }

}
