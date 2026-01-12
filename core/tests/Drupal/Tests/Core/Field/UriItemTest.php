<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\UriItem;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Defines a test for the UriItem field-type.
 */
#[CoversClass(UriItem::class)]
#[Group('Field')]
class UriItemTest extends UnitTestCase {

  /**
   * Tests generating sample values.
   *
   * @param int $max_length
   *   Maximum field length.
   */
  #[DataProvider('providerMaxLength')]
  public function testGenerateSampleValue(int $max_length): void {
    $definition = $this->prophesize(FieldDefinitionInterface::class);
    $definition->getSetting('max_length')->willReturn($max_length);

    for ($i = 0; $i < 1000; $i++) {
      $sample_value = UriItem::generateSampleValue($definition->reveal());
      $this->assertLessThanOrEqual($max_length, mb_strlen($sample_value['value']));
      $this->assertStringNotContainsString(' ', $sample_value['value']);
    }
  }

  /**
   * Data provider for maximum-lengths.
   *
   * @return array
   *   Test cases.
   */
  public static function providerMaxLength(): array {
    return [
      '32' => [32],
      '255' => [255],
      '500' => [500],
      '15' => [15],
      '64' => [64],
    ];
  }

}
