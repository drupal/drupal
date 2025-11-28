<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Unit\Plugin\migrate\process\d6;

use Drupal\field\Plugin\migrate\process\d6\FieldSettings;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests Drupal\field\Plugin\migrate\process\d6\FieldSettings.
 */
#[CoversClass(FieldSettings::class)]
#[Group('field')]
#[IgnoreDeprecations]
class FieldSettingsTest extends UnitTestCase {

  /**
   * Tests get settings.
   *
   * @legacy-covers ::getSettings
   */
  #[DataProvider('getSettingsProvider')]
  public function testGetSettings($field_type, $field_settings, $allowed_values): void {
    $plugin = new FieldSettings([], 'd6_field_settings', []);

    $executable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->getMockBuilder(Row::class)
      ->disableOriginalConstructor()
      ->getMock();

    $result = $plugin->transform([$field_type, $field_settings, NULL], $executable, $row, 'foo');
    $this->assertSame($allowed_values, $result['allowed_values']);
  }

  /**
   * Provides field settings for testGetSettings().
   */
  public static function getSettingsProvider() {
    return [
      [
        'list_integer',
        ['allowed_values' => "1|One\n2|Two\n3"],
        [
          '1' => 'One',
          '2' => 'Two',
          '3' => '3',
        ],
      ],
      [
        'list_string',
        ['allowed_values' => NULL],
        [],
      ],
      [
        'list_float',
        ['allowed_values' => ""],
        [],
      ],
      [
        'boolean',
        [],
        [],
      ],
    ];
  }

}
