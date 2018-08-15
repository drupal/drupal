<?php

namespace Drupal\Tests\node\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 view mode source plugin.
 *
 * @covers \Drupal\node\Plugin\migrate\source\d6\ViewMode
 *
 * @group node
 */
class ViewModeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['content_node_field_instance'] = [
      [
        'display_settings' => serialize([
          'weight' => '31',
          'parent' => '',
          'label' => [
            'format' => 'above',
          ],
          'teaser' => [
            'format' => 'default',
            'exclude' => 0,
          ],
          'full' => [
            'format' => 'default',
            'exclude' => 0,
          ],
          4 => [
            'format' => 'default',
            'exclude' => 0,
          ],
        ]),
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'entity_type' => 'node',
        'view_mode' => '4',
      ],
      [
        'entity_type' => 'node',
        'view_mode' => 'teaser',
      ],
      [
        'entity_type' => 'node',
        'view_mode' => 'full',
      ],
    ];

    return $tests;
  }

}
