<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Kernel\Plugin\migrate\source\d7;

use Drupal\image\Plugin\migrate\source\d7\ImageStyles;
use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore ieid isid
/**
 * Tests the D7 ImageStyles source plugin.
 */
#[CoversClass(ImageStyles::class)]
#[Group('image')]
#[RunTestsInSeparateProcesses]
class ImageStylesTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['image_styles'] = [
      [
        'isid' => 1,
        'name' => 'custom_image_style_1',
        'label' => 'Custom image style 1',
      ],
    ];
    $tests[0]['source_data']['image_effects'] = [
      [
        'ieid' => 1,
        'isid' => 1,
        'weight' => 1,
        'name' => 'image_desaturate',
        'data' => serialize([]),
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'isid' => 1,
        'name' => 'custom_image_style_1',
        'label' => 'Custom image style 1',
        'effects' => [
          [
            'ieid' => 1,
            'isid' => 1,
            'weight' => 1,
            'name' => 'image_desaturate',
            'data' => [],
          ],
        ],
      ],
    ];

    return $tests;
  }

}
