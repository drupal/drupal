<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit\Plugin\Validation\Constraint;

use Drupal\field\Plugin\Validation\Constraint\NoFieldItemsExistWithHigherCardinality;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the NoFieldItemsExistWithHigherCardinality constraint.
 */
#[CoversClass(NoFieldItemsExistWithHigherCardinality::class)]
#[Group('field')]
class NoFieldItemsExistWithHigherCardinalityTest extends UnitTestCase {

  /**
   * Tests the constraint initialization with valid options.
   */
  public function testValidOptions(): void {
    $options = [
      'entityType' => 'node',
      'fieldName' => 'field_test',
    ];

    $constraint = new NoFieldItemsExistWithHigherCardinality(entityType: $options['entityType'], fieldName: $options['fieldName']);

    $this->assertEquals('node', $constraint->entityType);
    $this->assertEquals('field_test', $constraint->fieldName);
    $this->assertEquals(
      "The field '@field_name' of entity type '@entity_type' has more entries (@max_delta) than the cardinality (@cardinality) allows.",
      $constraint->message
    );
  }

  /**
   * Tests the message template with different parameters.
   */
  #[DataProvider('messageParametersProvider')]
  public function testMessageParameters(string $entityType, string $fieldName, int $maxDelta, int $cardinality, string $expectedMessage): void {
    $options = [
      'entityType' => $entityType,
      'fieldName' => $fieldName,
    ];

    $constraint = new NoFieldItemsExistWithHigherCardinality(entityType: $options['entityType'], fieldName: $options['fieldName']);

    // Simulate the violation building process.
    $parameters = [
      '@field_name' => $fieldName,
      '@entity_type' => $entityType,
      '@max_delta' => (string) $maxDelta,
      '@cardinality' => (string) $cardinality,
    ];

    $message = strtr($constraint->message, $parameters);
    $this->assertEquals($expectedMessage, $message);
  }

  /**
   * Data provider for testMessageParameters.
   */
  public static function messageParametersProvider(): array {
    return [
      [
        'node',
        'field_body',
        3,
        2,
        "The field 'field_body' of entity type 'node' has more entries (3) than the cardinality (2) allows.",
      ],
      [
        'user',
        'field_address',
        5,
        1,
        "The field 'field_address' of entity type 'user' has more entries (5) than the cardinality (1) allows.",
      ],
    ];
  }

}
