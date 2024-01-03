<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Field;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;
use Drupal\Tests\UnitTestCase;

/**
 * Defines a test for the StringItem field-type.
 *
 * @group Field
 * @coversDefaultClass \Drupal\Core\Field\Plugin\Field\FieldType\StringItem
 */
class StringItemTest extends UnitTestCase {

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
    foreach ([TRUE, FALSE] as $unique) {
      $definition = $this->prophesize(FieldDefinitionInterface::class);
      $constraints = $unique ? [$this->prophesize(UniqueFieldConstraint::class)] : [];
      $definition->getConstraint('UniqueField')->willReturn($constraints);
      $definition->getSetting('max_length')->willReturn($max_length);
      for ($i = 0; $i < 1000; $i++) {
        $sample_value = StringItem::generateSampleValue($definition->reveal());
        // When the field value needs to be unique, the generated sample value
        // should match the maximum length to ensure sufficient entropy.
        if ($unique) {
          $this->assertEquals($max_length, mb_strlen($sample_value['value']));
        }
        else {
          $this->assertLessThanOrEqual($max_length, mb_strlen($sample_value['value']));
        }
      }
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
      '4' => [4],
      '64' => [64],
    ];
  }

}
