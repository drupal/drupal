<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests AtLeastOneOf validation constraint with both valid and invalid values.
 *
 * @covers \Drupal\Core\Validation\Plugin\Validation\Constraint\AtLeastOneOfConstraint
 * @covers \Drupal\Core\Validation\Plugin\Validation\Constraint\AtLeastOneOfConstraintValidator
 *
 * @group Validation
 */
class AtLeastOneOfConstraintValidatorTest extends KernelTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedData;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->typedData = $this->container->get('typed_data_manager');
  }

  /**
   * Tests the AllowedValues validation constraint validator.
   *
   * For testing we define an integer with a set of allowed values.
   *
   * @dataProvider dataProvider
   */
  public function testValidation($type, $value, $at_least_one_of_constraints, $expectedViolations, $extra_constraints = []): void {
    // Create a definition that specifies some AllowedValues.
    $definition = DataDefinition::create($type);

    if (count($extra_constraints) > 0) {
      foreach ($extra_constraints as $name => $settings) {
        $definition->addConstraint($name, $settings);
      }
    }

    $definition->addConstraint('AtLeastOneOf', [
      'constraints' => $at_least_one_of_constraints,
    ]);

    // Test the validation.
    $typed_data = $this->typedData->create($definition, $value);
    $violations = $typed_data->validate();

    $violationMessages = [];
    foreach ($violations as $violation) {
      $violationMessages[] = (string) $violation->getMessage();
    }

    $this->assertEquals($expectedViolations, $violationMessages, 'Validation passed for correct value.');
  }

  /**
   * Data provider for testValidation().
   */
  public static function dataProvider(): array {
    return [
      'It should fail on a failing sibling validator' => [
        'integer',
        1,
        [
          ['Range' => ['min' => 100]],
          ['NotNull' => []],
        ],
        ['This value should be blank.'],
        ['Blank' => []],
      ],
      'it should not fail if first validator fails' => [
        'integer',
        250,
        [
          ['AllowedValues' => [500]],
          ['Range' => ['min' => 100]],
        ],
        [],
      ],
      'it should not fail if second validator fails' => [
        'integer',
        250,
        [
          ['Range' => ['min' => 100]],
          ['AllowedValues' => [500]],
        ],
        [],
      ],
      'it should show multiple validation errors if none validate' => [
        'string',
        'Green',
        [
          ['AllowedValues' => ['test']],
          ['Blank' => []],
        ],
        [
          'This value should satisfy at least one of the following constraints: [1] The value you selected is not a valid choice. [2] This value should be blank.',
        ],
      ],
    ];
  }

}
