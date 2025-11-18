<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Validation\Plugin\Validation\Constraint\AtLeastOneOfConstraint;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests AtLeastOneOf validation constraint with both valid and invalid values.
 */
#[Group('Validation')]
#[CoversClass(AtLeastOneOfConstraint::class)]
#[RunTestsInSeparateProcesses]
class AtLeastOneOfConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->typedDataManager = $this->container->get('typed_data_manager');
  }

  /**
   * Tests the AllowedValues validation constraint validator.
   *
   * For testing we define an integer with a set of allowed values.
   */
  #[DataProvider('dataProvider')]
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
    $typed_data = $this->typedDataManager->create($definition, $value);
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
          ['AllowedValues' => ['choices' => [500]]],
          ['Range' => ['min' => 100]],
        ],
        [],
      ],
      'it should not fail if second validator fails' => [
        'integer',
        250,
        [
          ['Range' => ['min' => 100]],
          ['AllowedValues' => ['choices' => [500]]],
        ],
        [],
      ],
      'it should show multiple validation errors if none validate' => [
        'string',
        'Green',
        [
          ['AllowedValues' => ['choices' => ['test']]],
          ['Blank' => []],
        ],
        [
          'This value should satisfy at least one of the following constraints: [1] The value you selected is not a valid choice. [2] This value should be blank.',
        ],
      ],
    ];
  }

  /**
   * Tests use of AtLeastOneOf validation constraint in config.
   */
  public function testConfigValidation(): void {
    $this->installConfig('config_test');

    $config = \Drupal::configFactory()->getEditable('config_test.validation');
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager */
    $typed_config_manager = \Drupal::service('config.typed');

    $config->set('composite.at_least_one_of', 6);
    $result = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get())->validate();
    $this->assertCount(0, $result);

    $config->set('composite.at_least_one_of', 25);
    $result = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get())->validate();
    $this->assertCount(0, $result);

    $config->set('composite.at_least_one_of', 15);
    $result = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get())->validate();
    $this->assertCount(1, $result);
    $this->assertEquals('This value should satisfy at least one of the following constraints: [1] This value should be between 0 and 10. [2] This value should be between 20 and 30.', $result->get(0)->getMessage());
    $this->assertEquals('composite.at_least_one_of', $result->get(0)->getPropertyPath());
  }

}
