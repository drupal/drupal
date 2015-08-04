<?php

/**
 * @file
 * Contains \Drupal\Tests\field\Unit\Plugin\migrate\source\d6\FieldInstanceTest.
 */

namespace Drupal\Tests\field\Unit\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D6 field instance source plugin.
 *
 * @group field
 */
class FieldInstanceTest extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\field\Plugin\migrate\source\d6\FieldInstance';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = [
    // The id of the entity, can be any string.
    'id' => 'test_fieldinstance',
    // Leave it empty for now.
    'idlist' => [],
    'source' => [
      'plugin' => 'd6_field_instance',
    ],
  ];

  // We need to set up the database contents; it's easier to do that below.
  // These are sample result queries.
  protected $expectedResults = [
    [
      'field_name' => 'field_body',
      'type_name' => 'page',
      'weight' => 1,
      'label' => 'body',
      'widget_type' => 'text_textarea',
      'widget_settings' => '',
      'display_settings' => '',
      'description' => '',
      'widget_module' => 'text',
      'widget_active' => 1,
      'required' => 1,
      'active' => 1,
      'global_settings' => [],
    ],
  ];

  /**
   * Prepopulate contents with results.
   */
  protected function setUp() {
    $this->expectedResults[0]['widget_settings'] = [
      'rows' => 5,
      'size' => 60,
      'default_value' => [
        [
          'value' => '',
          '_error_element' => 'default_value_widget][field_body][0][value',
          'default_value_php' => '',
        ],
      ],
    ];
    $this->expectedResults[0]['display_settings'] = [
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
    ];
    $this->databaseContents['content_node_field_instance'] = $this->expectedResults;
    $this->databaseContents['content_node_field_instance'][0]['widget_settings'] = serialize($this->expectedResults[0]['widget_settings']);
    $this->databaseContents['content_node_field_instance'][0]['display_settings'] = serialize($this->expectedResults[0]['display_settings']);
    $this->databaseContents['content_node_field_instance'][0]['global_settings'] = 'a:0:{}';

    $this->databaseContents['content_node_field'][0] = [
      'field_name' => 'field_body',
      'required' => 1,
      'type' => 'text',
      'active' => 1,
      'global_settings' => serialize([]),
    ];
    parent::setUp();
  }

}
