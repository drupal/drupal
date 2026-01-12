<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit\Plugin\migrate\process\d7;

use Drupal\field\Plugin\migrate\process\d7\FieldInstanceSettings;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests Drupal\field\Plugin\migrate\process\d7\FieldInstanceSettings.
 */
#[CoversClass(FieldInstanceSettings::class)]
#[Group('field')]
#[IgnoreDeprecations]
class FieldInstanceSettingsTest extends MigrateTestCase {

  /**
   * Tests transformation of image field settings.
   */
  public function testTransformImageSettings(): void {
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
