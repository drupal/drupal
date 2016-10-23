<?php

namespace Drupal\Tests\field\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests d6_field_instance_per_form_display source plugin.
 *
 * @covers \Drupal\field\Plugin\migrate\source\d6\FieldInstancePerFormDisplay
 * @group field
 */
class FieldInstancePerFormDisplayTest extends MigrateSqlSourceTestBase {

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
        'display_settings' => [],
        'widget_settings' => [],
        'type_name' => 'story',
        'widget_active' => TRUE,
        'field_name' => 'field_test_filefield',
        'type' => 'filefield',
        'module' => 'filefield',
        'weight' => '8',
        'widget_type' => 'filefield_widget',
      ],
    ];

    // The source data.
    $empty_array = serialize([]);
    $tests[0]['source_data']['content_node_field'] = [
      [
        'field_name' => 'field_test_filefield',
        'type' => 'filefield',
        'global_settings' => $empty_array,
        'required' => '0',
        'multiple' => '0',
        'db_storage' => '1',
        'module' => 'filefield',
        'db_columns' => $empty_array,
        'active' => '1',
        'locked' => '0',
      ]
    ];
    $tests[0]['source_data']['content_node_field_instance'] = [
      [
        'field_name' => 'field_test_filefield',
        'type_name' => 'story',
        'weight' => '8',
        'label' => 'File Field',
        'widget_type' => 'filefield_widget',
        'widget_settings' => $empty_array,
        'display_settings' => $empty_array,
        'description' => 'An example image field.',
        'widget_module' => 'filefield',
        'widget_active' => '1',
      ],
    ];

    return $tests;
  }

}
