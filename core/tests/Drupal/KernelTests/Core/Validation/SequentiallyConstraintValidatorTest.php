<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\SequentiallyConstraint;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Sequentially validation constraint with both valid and invalid values.
 */
#[Group('Validation')]
#[CoversClass(SequentiallyConstraint::class)]
#[RunTestsInSeparateProcesses]
class SequentiallyConstraintValidatorTest extends KernelTestBase {


  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * The typed data manager to use.
   */
  protected TypedDataManagerInterface $typedData;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->typedData = $this->container->get('typed_data_manager');
  }

  /**
   * Tests use of Sequentially validation constraint in config.
   */
  public function testConfigValidation(): void {
    $this->installConfig('config_test');

    $config = \Drupal::configFactory()->getEditable('config_test.validation');
    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager */
    $typed_config_manager = \Drupal::service('config.typed');

    $config->set('composite.sequentially', 'green');
    $result = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get())->validate();
    $this->assertCount(0, $result);

    $config->set('composite.sequentially', '');
    $result = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get())->validate();
    $this->assertCount(1, $result);
    $this->assertEquals('This value should not be blank.', $result->get(0)->getMessage());
    $this->assertEquals('composite.sequentially', $result->get(0)->getPropertyPath());

    $config->set('composite.sequentially', 'im a very long string that should now work');
    $result = $typed_config_manager->createFromNameAndData('config_test.validation', $config->get())->validate();
    $this->assertCount(1, $result);
    $this->assertEquals('This value is too long. It should have 10 characters or less.', $result->get(0)->getMessage());
    $this->assertEquals('composite.sequentially', $result->get(0)->getPropertyPath());
  }

  /**
   * Tests the Sequentially validation constraint validator.
   */
  #[DataProvider('dataProvider')]
  public function testValidation(string $type, mixed $value, array $constraints, array $expectedViolations, array $extra_constraints = []): void {
    // Create a definition that specifies some AllowedValues.
    $definition = DataDefinition::create($type);

    if (count($extra_constraints) > 0) {
      foreach ($extra_constraints as $name => $settings) {
        $definition->addConstraint($name, $settings);
      }
    }

    $definition->addConstraint('Sequentially', [
      'constraints' => $constraints,
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
      'fail on a failing sibling validator' => [
        'integer',
        150,
        [
          ['Range' => ['min' => 100]],
          ['NotNull' => []],
        ],
        ['This value should be blank.'],
        ['Blank' => []],
      ],
      'fail if second validator fails' => [
        'integer',
        250,
        [
          ['Range' => ['min' => 100]],
          ['AllowedValues' => ['choices' => [500]]],
        ],
        [
          'The value you selected is not a valid choice.',
        ],
      ],
      'show first validation error only even when multiple would fail' => [
        'string',
        'Green',
        [
          ['AllowedValues' => ['choices' => ['test']]],
          ['Blank' => []],
        ],
        [
          'The value you selected is not a valid choice.',
        ],
      ],
    ];
  }

}
