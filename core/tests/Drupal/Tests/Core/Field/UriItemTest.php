<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\UriItem;
use Drupal\Tests\UnitTestCase;

/**
 * Defines a test for the UriItem field-type.
 *
 * @group Field
 * @coversDefaultClass \Drupal\Core\Field\Plugin\Field\FieldType\UriItem
 */
class UriItemTest extends UnitTestCase {

  /**
   * Tests generating sample values.
   *
   * @param int $max_length
   *   Maximum field length.
   *
   * @covers ::generateSampleValue
   * @dataProvider providerMaxLength
   */
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
  public function providerMaxLength(): array {
    return [
      '32' => [32],
      '255' => [255],
      '500' => [500],
      '15' => [15],
      '64' => [64],
    ];
  }

}
