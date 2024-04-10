<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 fields per view mode source plugin.
 *
 * @covers \Drupal\field\Plugin\migrate\source\d6\FieldInstancePerViewMode
 * @group field
 */
class FieldInstancePerViewModeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field', 'migrate_drupal', 'node', 'user'];

  /**
   * {@inheritdoc}
   */
  public static function providerSource() {
    $tests = [
      [
        'source_data' => [],
        'expected_data' => [],
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'entity_type' => 'node',
        'view_mode' => 4,
        'type_name' => 'article',
        'field_name' => 'field_test',
        'type' => 'text',
        'module' => 'text',
        'weight' => 1,
        'label' => 'above',
        'display_settings' => [
          'weight' => 1,
          'parent' => '',
          'label' => [
            'format' => 'above',
          ],
          4 => [
            'format' => 'trimmed',
            'exclude' => 0,
          ],
        ],
        'widget_settings' => [],
      ],
      [
        'entity_type' => 'node',
        'view_mode' => 'teaser',
        'type_name' => 'story',
        'field_name' => 'field_test',
        'type' => 'text',
        'module' => 'text',
        'weight' => 2,
        'label' => 'above',
        'display_settings' => [
          'weight' => 1,
          'parent' => '',
          'label' => [
            'format' => 'above',
          ],
          'teaser' => [
            'format' => 'trimmed',
            'exclude' => 0,
          ],
        ],
        'widget_settings' => [],
      ],
    ];

    // The source data.
    foreach ($tests[0]['expected_data'] as $k => $field_view_mode) {
      // These are stored as serialized strings.
      $field_view_mode['display_settings'] = serialize($field_view_mode['display_settings']);
      $field_view_mode['widget_settings'] = serialize($field_view_mode['widget_settings']);

      $tests[0]['source_data']['content_node_field'][] = [
        'field_name' => $field_view_mode['field_name'],
        'type' => $field_view_mode['type'],
        'module' => $field_view_mode['module'],
      ];
      unset($field_view_mode['type'], $field_view_mode['module']);

      $tests[0]['source_data']['content_node_field_instance'][] = $field_view_mode;

      // Update the expected display settings.
      $tests[0]['expected_data'][$k]['display_settings'] = $tests[0]['expected_data'][$k]['display_settings'][$field_view_mode['view_mode']];
    }

    return $tests;
  }

}
