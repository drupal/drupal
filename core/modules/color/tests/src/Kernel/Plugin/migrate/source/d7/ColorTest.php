<?php

namespace Drupal\Tests\color\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 color source plugin.
 *
 * @covers \Drupal\color\Plugin\migrate\source\d7\Color
 *
 * @group color
 */
class ColorTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['color', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['database']['variable'] = [
      [
        'name' => 'color_bartik_palette',
        'value' => [
          'top' => '#cd2d2d',
          'bottom' => '#d64e4e',
          'bg' => '#ffffff',
          'sidebar' => '#f1f4f0',
          'sidebarborders' => '#ededed',
          'footer' => '#1f1d1c',
          'titleslogan' => '#fffeff',
          'text' => '#888888',
          'link' => '#d6121f',
        ],
      ],
      [
        'name' => 'color_bartik_logo',
        'value' => 'public://color/bartik-e0e23ad7/logo.png',
      ],
      [
        'name' => 'color_bartik_stylesheets',
        'value' => ['public://color/bartik-1d249313/colors.css'],
      ],
      [
        'name' => 'color_bartik_files',
        'value' => [
          'public://color/bartik-e0e23ad7/logo.png',
          'public://color/bartik-e0e23ad7/colors.css',
        ],
      ],
      [
        'name' => 'color_bartik_screenshot',
        'value' => ['public:://color/bartik-b69cfcec/screenshot.png'],
      ],
      [
        'name' => 'color_custom_stylesheets',
        'value' => ['public:://color/custom-beadedff/colors.css'],
      ],
    ];

    foreach ($tests[0]['database']['variable'] as $key => $expected) {
      $tests[0]['database']['variable'][$key]['value'] = serialize($expected['value']);
    }

    $tests[0]['database']['system'] = [
      [
        'name' => 'bartik',
        'type' => 'theme',
        'status' => '1',
      ],
      [
        'name' => 'custom',
        'type' => 'theme',
        'status' => '0',
      ],
    ];

    // Expected results are the same as the source.
    $tests[0]['expected_results'] = [
      [
        'name' => 'color_bartik_palette',
        'value' => [
          'top' => '#cd2d2d',
          'bottom' => '#d64e4e',
          'bg' => '#ffffff',
          'sidebar' => '#f1f4f0',
          'sidebarborders' => '#ededed',
          'footer' => '#1f1d1c',
          'titleslogan' => '#fffeff',
          'text' => '#888888',
          'link' => '#d6121f',
        ],
      ],
      [
        'name' => 'color_bartik_logo',
        'value' => 'public://color/bartik-e0e23ad7/logo.png',
      ],
      [
        'name' => 'color_bartik_stylesheets',
        'value' => ['public://color/bartik-1d249313/colors.css'],
      ],
      [
        'name' => 'color_bartik_files',
        'value' => [
          'public://color/bartik-e0e23ad7/logo.png',
          'public://color/bartik-e0e23ad7/colors.css',
        ],
      ],
      [
        'name' => 'color_bartik_screenshot',
        'value' => ['public:://color/bartik-b69cfcec/screenshot.png'],
      ],
      [
        'name' => 'color_custom_stylesheets',
        'value' => ['public:://color/custom-beadedff/colors.css'],
      ],
    ];

    return $tests;
  }

}
