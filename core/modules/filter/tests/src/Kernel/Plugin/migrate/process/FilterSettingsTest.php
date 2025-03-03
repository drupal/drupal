<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel\Plugin\migrate\process;

use Drupal\filter\Plugin\migrate\process\FilterSettings;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Unit\MigrateTestCase;

/**
 * Unit tests of the filter_settings plugin.
 *
 * @coversDefaultClass \Drupal\filter\Plugin\migrate\process\FilterSettings
 * @group filter
 */
class FilterSettingsTest extends MigrateTestCase {

  /**
   * Tests transformation of filter settings.
   *
   * @dataProvider dataProvider
   * @covers ::transform
   */
  public function testTransform($value, $destination_id, $expected_value): void {
    $plugin = new FilterSettings([], 'filter_settings', []);

    $executable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->getMockBuilder(Row::class)
      ->disableOriginalConstructor()
      ->getMock();

    $row->expects($this->atLeastOnce())
      ->method('getDestinationProperty')
      ->willReturn($destination_id);

    $output_value = $plugin->transform($value, $executable, $row, 'foo');
    $this->assertSame($expected_value, $output_value);
  }

  /**
   * The test data provider.
   *
   * @return array
   *   An array of test data.
   */
  public static function dataProvider() {
    return [
      // Tests that the transformed value is identical to the input value when
      // destination is not the filter_html.

      // Test with an empty source array.
      [
        [],
        'any_filter',
        [],
      ],
      // Test with a source string.
      [
        'a string',
        'any_filter',
        'a string',
      ],
      // Test with a source filter array.
      [
        [
          'allowed_html' => '<a> <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd>',
        ],
        'any_filter',
        [
          'allowed_html' => '<a> <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd>',
        ],
      ],

      // Tests that the transformed value for 'allowed_html' is altered when the
      // destination is filter_html.

      // Test with an empty source array.
      [
        [],
        'filter_html',
        [],
      ],
      // Test with a source string.
      [
        'a string',
        'filter_html',
        'a string',
      ],
      [
        [
          'allowed_html' => '<a> <em> <strong> <cite> <code> <ul> <ol> <li> <dl> <dt> <dd>',
        ],
        'filter_html',
        [
          'allowed_html' => '<a href hreflang> <em> <strong> <cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd>',
        ],
      ],
      [
        [
          'foo' => 'bar',
        ],
        'filter_null',
        [],
      ],
    ];
  }

}
