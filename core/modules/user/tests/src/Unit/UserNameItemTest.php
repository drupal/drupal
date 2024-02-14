<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserNameItem;

/**
 * Defines a test for the UserNameItem field-type.
 *
 * @group Field
 * @coversDefaultClass \Drupal\user\UserNameItem
 */
class UserNameItemTest extends UnitTestCase {

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
      $sample_value = UserNameItem::generateSampleValue($definition->reveal());
      $this->assertLessThanOrEqual($max_length, mb_strlen($sample_value['value']));
      $this->assertEquals(trim($sample_value['value'], ' '), $sample_value['value']);
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
