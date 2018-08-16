<?php

namespace Drupal\Tests\field\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 field instance source plugin.
 *
 * @covers \Drupal\field\Plugin\migrate\source\d6\FieldInstance
 * @group field
 */
class FieldInstanceTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [
      [
        'source_data' => [],
        'expected_data' => [],
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'field_name' => 'field_body',
        'type_name' => 'page',
        'weight' => 1,
        'label' => 'body',
        'widget_type' => 'text_textarea',
        'description' => '',
        'widget_module' => 'text',
        'widget_active' => 1,
        'required' => 1,
        'active' => 1,
        'global_settings' => [],
        'widget_settings' => [
          'rows' => 5,
          'size' => 60,
          'default_value' => [
            [
              'value' => '',
              '_error_element' => 'default_value_widget][field_body][0][value',
              'default_value_php' => '',
            ],
          ],
        ],
        'display_settings' => [
          'label' => [
            'format' => 'above',
            'exclude' => 0,
          ],
          'teaser' => [
            'format' => 'default',
            'exclude' => 0,
          ],
          'full' => [
            'format' => 'default',
            'exclude' => 0,
          ],
        ],
      ],
    ];

    // The source data.
    $tests[0]['source_data']['content_node_field_instance'] = array_map(
      function (array $row) {
        $row['widget_settings'] = serialize($row['widget_settings']);
        $row['display_settings'] = serialize($row['display_settings']);
        $row['global_settings'] = serialize($row['global_settings']);
        return $row;
      },
      $tests[0]['expected_data']
    );
    $tests[0]['source_data']['content_node_field'] = [
      [
        'field_name' => 'field_body',
        'required' => 1,
        'type' => 'text',
        'active' => 1,
        'global_settings' => serialize([]),
      ],
    ];

    return $tests;
  }

}
