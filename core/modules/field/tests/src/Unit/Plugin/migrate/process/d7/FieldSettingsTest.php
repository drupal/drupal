<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit\Plugin\migrate\process\d7;

use Drupal\field\Plugin\migrate\process\d7\FieldSettings;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests Drupal\field\Plugin\migrate\process\d7\FieldSettings.
 */
#[CoversClass(FieldSettings::class)]
#[Group('field')]
#[IgnoreDeprecations]
class FieldSettingsTest extends MigrateTestCase {

  /**
   * Tests transformation of image field settings.
   */
  public function testTransformImageSettings(): void {
    $plugin = new FieldSettings([], 'd7_field_settings', []);

    $executable = $this->createMock(MigrateExecutableInterface::class);
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
    $this->assertIsArray($value);
    $this->assertSame('', $value['default_image']['uuid']);
  }

}
