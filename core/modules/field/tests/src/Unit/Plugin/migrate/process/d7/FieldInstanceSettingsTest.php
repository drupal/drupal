<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit\Plugin\migrate\process\d7;

use Drupal\field\Plugin\migrate\process\d7\FieldInstanceSettings;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * @coversDefaultClass \Drupal\field\Plugin\migrate\process\d7\FieldInstanceSettings
 * @group field
 */
class FieldInstanceSettingsTest extends MigrateTestCase {

  /**
   * Tests transformation of image field settings.
   *
   * @covers ::transform
   */
  public function testTransformImageSettings(): void {
    $migration = $this->createMock(MigrationInterface::class);
    $plugin = new FieldInstanceSettings([], 'd7_field_instance_settings', []);

    $executable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->getMockBuilder(Row::class)
      ->disableOriginalConstructor()
      ->getMock();

    $value = $plugin->transform([[], ['type' => 'image_image'], ['data' => '']], $executable, $row, 'foo');
    $this->assertIsArray($value['default_image']);
    $this->assertSame('', $value['default_image']['alt']);
    $this->assertSame('', $value['default_image']['title']);
    $this->assertNull($value['default_image']['width']);
    $this->assertNull($value['default_image']['height']);
    $this->assertSame('', $value['default_image']['uuid']);
  }

}
