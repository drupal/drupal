<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme\DesignToken;

use Drupal\Core\Theme\Plugin\Validation\Constraint\CssSelectorConstraint;
use Drupal\Core\Theme\Plugin\Validation\Constraint\CssSelectorConstraintValidator;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Tests Css Selector Constraint Validator.
 */
#[Group('design_token')]
#[Group('Validation')]
#[CoversClass(CssSelectorConstraint::class)]
#[CoversClass(CssSelectorConstraintValidator::class)]
#[RunTestsInSeparateProcesses]
class CssSelectorConstraintValidatorTest extends KernelTestBase {

  /**
   * Tests validation of CSS selector.
   */
  public function testValidation(): void {
    $definition = DataDefinition::create('string')
      ->addConstraint('CssSelector');
    $data = $this->container->get(TypedDataManagerInterface::class)->create($definition);

    $cases = [
      'valid' => [
        'value' => '.root',
        'violations' => [],
      ],
      'valid_with_config_storage_character' => [
        'value' => '%root',
        'violations' => [],
      ],
      'invalid_{' => [
        'value' => '{root',
        'violations' => [
          'The CSS selector {root is invalid.',
        ],
      ],
      'invalid_}' => [
        'value' => '}root',
        'violations' => [
          'The CSS selector }root is invalid.',
        ],
      ],
      'invalid_;' => [
        'value' => '.root;',
        'violations' => [
          'The CSS selector .root; is invalid.',
        ],
      ],
    ];

    foreach ($cases as $case) {
      $data->setValue($case['value']);
      $violations = $data->validate();
      $this->assertCount(count($case['violations']), $violations);
      foreach ($case['violations'] as $violationKey => $expectedViolationMessage) {
        $this->assertSame($expectedViolationMessage, (string) $violations[$violationKey]->getMessage());
      }
    }
  }

  /**
   * Tests invalid type.
   */
  public function testValidatedValueMustBeAString(): void {
    $definition = DataDefinition::create('string')
      ->addConstraint('CssSelector');
    $data = $this->container->get(TypedDataManagerInterface::class)->create($definition);
    $data->setValue(5);

    $this->expectException(UnexpectedValueException::class);
    $this->expectExceptionMessage('Expected argument of type "string", "int" given');
    $data->validate();
  }

}
