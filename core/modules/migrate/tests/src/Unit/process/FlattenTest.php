<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\process\Flatten;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the flatten plugin.
 */
#[Group('migrate')]
class FlattenTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->plugin = new Flatten([], 'flatten', []);
    parent::setUp();
  }

  /**
   * Tests that various array flatten operations work properly.
   */
  #[DataProvider('providerTestFlatten')]
  public function testFlatten($value, array $expected): void {
    $flattened = $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'destination_property');
    $this->assertSame($expected, $flattened);
  }

  /**
   * Provides data for the testFlatten.
   */
  public static function providerTestFlatten(): array {
    $object = (object) [
      'a' => 'test',
      'b' => '1.2',
      'c' => 'NULL',
    ];
    return [
      'array' => [
        [1, 2, [3, 4, [5]], [], [7, 8]],
        [1, 2, 3, 4, 5, 7, 8],
      ],
      'object' => [
        $object,
        ['test', '1.2', 'NULL'],
      ],
    ];
  }

  /**
   * Tests that Flatten throws a MigrateException.
   */
  #[DataProvider('providerTestFlattenInvalid')]
  public function testFlattenInvalid(string|int|float|bool|null $value): void {
    $this->expectException(MigrateException::class);
    $type = gettype($value);
    $this->expectExceptionMessageIs(sprintf("Input should be an array or an object, instead it was of type '%s'", $type));
    $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'destination_property');
  }

  /**
   * Provides data for the testFlattenInvalid.
   */
  public static function providerTestFlattenInvalid(): array {
    $xml_str = <<<XML
<xml version='1.0'?>
<authors>
 <name>Ada Lovelace</name>
</authors>
XML;
    return [
      'empty string' => [
        '',
      ],
      'string' => [
        'Kate Sheppard',
      ],
      'integer' => [
        1,
      ],
      'float' => [
        1.2,
      ],
      'NULL' => [
        NULL,
      ],
      'boolean' => [
        TRUE,
      ],
      'xml' => [
        $xml_str,
      ],
    ];
  }

}
