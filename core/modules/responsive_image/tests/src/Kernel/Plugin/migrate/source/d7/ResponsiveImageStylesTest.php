<?php

declare(strict_types=1);

namespace Drupal\Tests\responsive_image\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 responsive image styles source plugin.
 *
 * @covers \Drupal\responsive_image\Plugin\migrate\source\d7\ResponsiveImageStyles
 * @group image
 */
class ResponsiveImageStylesTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate_drupal',
    'responsive_image',
  ];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['picture_mapping'] = [
      [
        'label' => 'Narrow',
        'machine_name' => 'narrow',
        'breakpoint_group' => 'responsive_image',
        'mapping' => 'a:2:{s:38:"breakpoints.theme.my_theme_id.computer";a:3:{s:12:"multiplier_1";a:2:{s:12:"mapping_type";s:11:"image_style";s:11:"image_style";s:20:"custom_image_style_1";}s:12:"multiplier_2";a:3:{s:12:"mapping_type";s:5:"sizes";s:5:"sizes";i:2;s:18:"sizes_image_styles";a:2:{i:0;s:20:"custom_image_style_1";i:1;s:20:"custom_image_style_2";}}s:12:"multiplier_3";a:1:{s:12:"mapping_type";s:5:"_none";}}s:42:"breakpoints.theme.my_theme_id.computer_two";a:1:{s:12:"multiplier_2";a:3:{s:12:"mapping_type";s:5:"sizes";s:5:"sizes";i:2;s:18:"sizes_image_styles";a:2:{i:0;s:20:"custom_image_style_1";i:1;s:20:"custom_image_style_2";}}}}',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'label' => 'Narrow',
        'machine_name' => 'narrow',
        'breakpoint_group' => 'responsive_image',
        'mapping' => [
          'breakpoints.theme.my_theme_id.computer' =>
            [
              'multiplier_1' =>
                [
                  'mapping_type' => 'image_style',
                  'image_style' => 'custom_image_style_1',
                ],
              'multiplier_2' =>
                [
                  'mapping_type' => 'sizes',
                  'sizes' => 2,
                  'sizes_image_styles' =>
                    [
                      0 => 'custom_image_style_1',
                      1 => 'custom_image_style_2',
                    ],
                ],
              'multiplier_3' =>
                [
                  'mapping_type' => '_none',
                ],
            ],
          'breakpoints.theme.my_theme_id.computer_two' =>
            [
              'multiplier_2' =>
                [
                  'mapping_type' => 'sizes',
                  'sizes' => 2,
                  'sizes_image_styles' =>
                    [
                      0 => 'custom_image_style_1',
                      1 => 'custom_image_style_2',
                    ],
                ],
            ],
        ],
      ],
    ];

    return $tests;
  }

}
