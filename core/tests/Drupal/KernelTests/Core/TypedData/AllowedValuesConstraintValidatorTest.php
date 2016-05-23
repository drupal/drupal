<?php

namespace Drupal\KernelTests\Core\TypedData;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;

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

  protected function setUp() {
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
      ->addConstraint('AllowedValues', array(1, 2, 3));

    // Test the validation.
    $typed_data = $this->typedData->create($definition, 1);
    $violations = $typed_data->validate();
    $this->assertEqual($violations->count(), 0, 'Validation passed for correct value.');

    // Test the validation when an invalid value is passed.
    $typed_data = $this->typedData->create($definition, 4);
    $violations = $typed_data->validate();
    $this->assertEqual($violations->count(), 1, 'Validation failed for incorrect value.');

    // Make sure the information provided by a violation is correct.
    $violation = $violations[0];
    $this->assertEqual($violation->getMessage(), t('The value you selected is not a valid choice.'), 'The message for invalid value is correct.');
    $this->assertEqual($violation->getRoot(), $typed_data, 'Violation root is correct.');
    $this->assertEqual($violation->getInvalidValue(), 4, 'The invalid value is set correctly in the violation.');
  }

}
