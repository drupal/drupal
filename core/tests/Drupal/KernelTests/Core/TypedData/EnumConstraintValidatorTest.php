<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\TypedData;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\EnumConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\EnumConstraintValidator;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\Component\Serialization\BackedEnumValue;
use Drupal\Tests\Component\Serialization\EnumValue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the "enum" data type.
 */
#[CoversClass(EnumConstraint::class)]
#[CoversClass(EnumConstraintValidator::class)]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
class EnumConstraintValidatorTest extends KernelTestBase {

  /**
   * The typed data manager to use.
   */
  protected TypedDataManagerInterface $typedDataManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->typedDataManager = $this->container->get('typed_data_manager');
  }

  /**
   * Tests the constraint.
   */
  public function testValidation(): void {
    $definition = DataDefinition::create('enum');
    $definition['enum_class'] = EnumValue::class;
    $enum_data = $this->typedDataManager->create($definition);

    $violations = $enum_data->validate();
    $this->assertCount(0, $violations);

    // Test with missing class in definition.
    $invalid_definition = DataDefinition::create('enum');
    $invalid_enum_data = $this->typedDataManager->create($invalid_definition);
    $invalid_enum_data->setValue(EnumValue::Yes);
    $violations = $invalid_enum_data->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('The Enum data definition must supply an "enum_class".', $violations[0]->getMessage());

    // Test with wrong class.
    $enum_data_wrong_class = $this->typedDataManager->create($definition);
    $enum_data_wrong_class->setValue(BackedEnumValue::No);
    $violations = $enum_data_wrong_class->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('The value must be an instance of Drupal\Tests\Component\Serialization\EnumValue.', $violations[0]->getMessage());

    // Test with not an enum.
    $definition_std = DataDefinition::create('enum');
    $definition_std['enum_class'] = \stdClass::class;
    $enum_data_std = $this->typedDataManager->create($definition_std);
    $enum_data_std->setValue(new \stdClass());
    $violations = $enum_data_std->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('The value you selected is not a valid enum.', $violations[0]->getMessage());
  }

}
