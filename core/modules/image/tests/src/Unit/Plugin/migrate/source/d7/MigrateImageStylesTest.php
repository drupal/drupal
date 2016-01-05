<?php

/**
 * @file
 * Contains \Drupal\Tests\image\Unit\Plugin\migrate\source\d7\MigrateImageStylesTest.
 */

namespace Drupal\Tests\image\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 ImageStyles source plugin.
 *
 * @group image
 */
class MigrateImageStylesTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\image\Plugin\migrate\source\d7\ImageStyles';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_image_styles',
    ],
  ];

  protected $expectedResults = [
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
        ]
      ]
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    foreach ($this->expectedResults as $k => $row) {
      foreach (array('isid', 'name', 'label') as $field) {
        $this->databaseContents['image_styles'][$k][$field] = $row[$field];
      }
      foreach ($row['effects'] as $id => $effect) {
        $row['effects'][$id]['data'] = serialize($row['effects'][$id]['data']);
      }
      $this->databaseContents['image_effects'] = $row['effects'];
    }
    parent::setUp();
  }

}
