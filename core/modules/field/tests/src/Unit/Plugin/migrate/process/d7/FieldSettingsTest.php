<?php

/**
 * @file
 * Contains \Drupal\Tests\field\Unit\Plugin\migrate\process\d7\FieldInstanceSettingsTest.
 */

namespace Drupal\Tests\field\Unit\Plugin\migrate\process\d7;

use Drupal\field\Plugin\migrate\process\d7\FieldSettings;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * @coversDefaultClass \Drupal\field\Plugin\migrate\process\d7\FieldSettings
 * @group field
 */
class FieldSettingsTest extends MigrateTestCase {

  /**
   * Tests transformation of image field settings.
   *
   * @covers ::transform
   */
  public function testTransformImageSettings() {
    $migration = $this->getMock(MigrationInterface::class);
    $plugin = new FieldSettings([], 'd7_field_settings', [], $migration);

    $executable = $this->getMock(MigrateExecutableInterface::class);
    $row = $this->getMockBuilder(Row::class)
      ->disableOriginalConstructor()
      ->getMock();

    $row->expects($this->atLeastOnce())
      ->method('getSourceProperty')
      ->willReturnMap([
        ['settings', ['default_image' => NULL]],
        ['type', 'image'],
      ]);

    $value = $plugin->transform([], $executable, $row, 'foo');
    $this->assertInternalType('array', $value);
    $this->assertSame('', $value['default_image']['uuid']);
  }

}
