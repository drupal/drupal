<?php

namespace Drupal\Tests\field\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 field source plugin.
 *
 * @covers \Drupal\field\Plugin\migrate\source\d6\Field
 * @group field
 */
class FieldTest extends MigrateSqlSourceTestBase {

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
        'type' => 'text',
        'global_settings' => [
          'text_processing' => 0,
          'max_length' => '',
          'allowed_values' => '',
          'allowed_values_php' => '',
        ],
        'required' => 0,
        'multiple' => 0,
        'db_storage' => 1,
        'module' => 'text',
        'db_columns' => [
          'value' => [
            'type' => 'text',
            'size' => 'big',
            'not null' => '',
            'sortable' => 1,
            'views' => 1,
          ],
        ],
        'active' => 1,
        'locked' => 0,
      ],
    ];

    // The source data.
    $tests[0]['source_data']['content_node_field'] = array_map(
      function (array $row) {
        $row['global_settings'] = serialize($row['global_settings']);
        $row['db_columns'] = serialize($row['db_columns']);
        return $row;
      },
      $tests[0]['expected_data']
    );
    $tests[0]['source_data']['content_node_field_instance'] = [
      [
        'widget_type' => 'text_textarea',
        'field_name' => 'field_body',
      ],
    ];

    return $tests;
  }

}
