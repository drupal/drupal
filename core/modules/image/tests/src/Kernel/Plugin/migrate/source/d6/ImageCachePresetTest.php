<?php

namespace Drupal\Tests\image\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the d6_imagecache_presets source plugin.
 *
 * @covers \Drupal\image\Plugin\migrate\source\d6\ImageCachePreset
 *
 * @group image
 */
class ImageCachePresetTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['imagecache_preset'] = [
      [
        'presetid' => '1',
        'presetname' => 'slackjaw_boys',
      ],
    ];
    $tests[0]['source_data']['imagecache_action'] = [
      [
        'actionid' => '3',
        'presetid' => '1',
        'weight' => '0',
        'module' => 'imagecache',
        'action' => 'imagecache_scale_and_crop',
        'data' => 'a:2:{s:5:"width";s:4:"100%";s:6:"height";s:4:"100%";}',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'presetid' => '1',
        'presetname' => 'slackjaw_boys',
        'actions' => [
          [
            'actionid' => '3',
            'presetid' => '1',
            'weight' => '0',
            'module' => 'imagecache',
            'action' => 'imagecache_scale_and_crop',
            'data' => [
              'width' => '100%',
              'height' => '100%',
            ],
          ],
        ],
      ],
    ];

    return $tests;
  }

}
