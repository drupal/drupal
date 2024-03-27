<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Component;

use Drupal\Core\Theme\Component\SchemaCompatibilityChecker;
use Drupal\Core\Render\Component\Exception\IncompatibleComponentSchema;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Component\SchemaCompatibilityChecker
 * @group sdc
 */
class SchemaCompatibilityCheckerTest extends UnitTestCase {

  /**
   * The system under test.
   */
  protected SchemaCompatibilityChecker $checker;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->checker = new SchemaCompatibilityChecker();
  }

  /**
   * @covers ::isCompatible
   * @dataProvider dataProviderIsCompatible
   */
  public function testIsCompatible(array $first_schema, array $second_schema, bool $expected): void {
    try {
      $this->checker->isCompatible($first_schema, $second_schema);
      $is_compatible = TRUE;
    }
    catch (IncompatibleComponentSchema $e) {
      $is_compatible = FALSE;
    }
    $this->assertSame($expected, $is_compatible);
  }

  /**
   * Data provider for the test testIsCompatible.
   *
   * @return array[]
   *   The batches of data.
   */
  public static function dataProviderIsCompatible(): array {
    $schema = [
      'type' => 'object',
      'required' => ['text'],
      'properties' => [
        'text' => [
          'type' => 'string',
          'title' => 'Title',
          'description' => 'The title for the button',
          'minLength' => 2,
          'examples' => ['Press', 'Submit now'],
        ],
        'iconType' => [
          'type' => 'string',
          'title' => 'Icon Type',
          'enum' => ['power', 'like', 'external'],
        ],
      ],
    ];
    $schema_different_required = [...$schema, 'required' => ['foo']];
    $schema_missing_icon_type = $schema;
    unset($schema_missing_icon_type['properties']['iconType']);
    $schema_missing_text = $schema;
    unset($schema_missing_text['properties']['text']);
    $schema_icon_with_number = $schema;
    $schema_icon_with_number['properties']['iconType']['type'] = [
      'string',
      'number',
    ];
    $schema_additional_enum = $schema;
    $schema_additional_enum['properties']['iconType']['enum'][] = 'wow';
    $schema_with_sub_schema = $schema;
    $schema_with_sub_schema['properties']['parent'] = $schema;
    $schema_with_sub_schema_enum = $schema;
    $schema_with_sub_schema_enum['properties']['parent'] = $schema_additional_enum;
    return [
      [$schema, $schema, TRUE],
      [$schema, $schema_different_required, FALSE],
      [$schema_different_required, $schema, FALSE],
      [$schema_missing_icon_type, $schema, TRUE],
      [$schema, $schema_missing_icon_type, TRUE],
      [$schema_missing_text, $schema, TRUE],
      [$schema, $schema_missing_text, TRUE],
      [$schema_icon_with_number, $schema, FALSE],
      [$schema, $schema_icon_with_number, TRUE],
      [$schema, $schema_additional_enum, TRUE],
      [$schema_additional_enum, $schema, FALSE],
      [$schema_with_sub_schema, $schema_with_sub_schema, TRUE],
      [$schema_with_sub_schema, $schema_with_sub_schema_enum, TRUE],
      [$schema_with_sub_schema_enum, $schema_with_sub_schema, FALSE],
    ];
  }

}
