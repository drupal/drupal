<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\ValidSequenceKeysConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\ValidSequenceKeysConstraintValidator;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests ValidSequenceKeys validation constraint with both valid and invalid values.
 */
#[Group('Validation')]
#[CoversClass(ValidSequenceKeysConstraint::class)]
#[CoversClass(ValidSequenceKeysConstraintValidator::class)]
#[RunTestsInSeparateProcesses]
class ValidSequenceKeysValidatorTest extends KernelTestBase {

  /**
   * The typed data manager to use.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected TypedDataManagerInterface $typedData;

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected TypedConfigManagerInterface $typedConfigManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->typedData = $this->container->get('typed_data_manager');
    $this->typedConfigManager = $this->container->get('config.typed');
  }

  /**
   * Tests the AllowedValues validation constraint validator.
   *
   * For testing we define an integer with a set of allowed values.
   */
  #[DataProvider('dataProvider')]
  public function testValidation(array $value, array $constraints, array $expected_violations, array $extra_constraints = []): void {

    /** @var \Drupal\Core\TypedData\MapDataDefinition $definition */
    $definition = $this->typedData->createDataDefinition('map');
    $definition->setPropertyDefinition('keys', $this->typedConfigManager->createDataDefinition('sequence'));

    if (count($extra_constraints) > 0) {
      foreach ($extra_constraints as $name => $settings) {
        $definition->addConstraint($name, $settings);
      }
    }

    $definition->addConstraint('ValidSequenceKeys', [
      'constraints' => $constraints,
    ]);

    $typed_data = $this->typedConfigManager->create($definition);
    $typed_data->setValue($value);

    $violations = $typed_data->validate();
    $violationMessages = [];
    foreach ($violations as $violation) {
      $violationMessages[] = (string) $violation->getMessage();
    }

    $this->assertEquals($expected_violations, $violationMessages, 'Validation did not pass.');
  }

  /**
   * Data provider for test.
   */
  public static function dataProvider(): array {
    return [
      'It should fail on a failing sibling validator' => [
        'value' => ['system' => 1, 'node' => 1],
        'constraints' => [
          'ExtensionName' => [],
          'ExtensionAvailable' => ['type' => 'module'],
        ],
        'expected_violations' => [
          'This value should be blank.',
        ],
        'extra_constraints' => ['Blank' => []],
      ],
      'it should fail if first validator fails' => [
        'value' => ['system1' => 1, 'stark' => 1],
        'constraints' => [
          'ExtensionAvailable' => ['type' => 'theme'],
          'ExtensionName' => [],
          'Blank' => [],
        ],
        'expected_violations' => [
          'Theme \'system1\' is not available.',
          'This value should be blank.',
          'This value should be blank.',
          'The keys of the sequence do not match the given constraints.',
        ],
      ],
      'it should fail if second validator fails' => [
        'value' => ['red' => 0],
        'constraints' => [
          'ExtensionName' => [],
          'ExtensionAvailable' => ['type' => 'module'],
          'Blank' => [],
        ],
        'expected_violations' => [
          'Module \'red\' is not available.',
          'This value should be blank.',
          'The keys of the sequence do not match the given constraints.',
        ],
      ],
    ];
  }

}
