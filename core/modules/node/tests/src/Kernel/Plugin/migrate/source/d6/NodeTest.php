<?php

namespace Drupal\Tests\node\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

// cspell:ignore tnid

/**
 * Tests D6 node source plugin.
 *
 * @covers \Drupal\node\Plugin\migrate\source\d6\Node
 *
 * @group node
 */
class NodeTest extends MigrateSqlSourceTestBase {

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
    $tests[0]['source_data']['content_node_field'] = [
      [
        'field_name' => 'field_test_four',
        'type' => 'number_float',
        'global_settings' => 'a:0:{}',
        'required' => '0',
        'multiple' => '0',
        'db_storage' => '1',
        'module' => 'number',
        'db_columns' => 'a:1:{s:5:"value";a:3:{s:4:"type";s:5:"float";s:8:"not null";b:0;s:8:"sortable";b:1;}}',
        'active' => '1',
        'locked' => '0',
      ],
      [
        'field_name' => 'field_test_empty_db_columns',
        'type' => 'empty_db_columns',
        'global_settings' => 'a:0:{}',
        'required' => '0',
        'multiple' => '0',
        'db_storage' => '1',
        'module' => 'empty_db_columns',
        'db_columns' => 'a:0:{}',
        'active' => '1',
        'locked' => '0',
      ],
    ];
    $tests[0]['source_data']['content_node_field_instance'] = [
      [
        'field_name' => 'field_test_four',
        'type_name' => 'story',
        'weight' => '3',
        'label' => 'Float Field',
        'widget_type' => 'number',
        'widget_settings' => 'a:0:{}',
        'display_settings' => 'a:0:{}',
        'description' => 'An example float field.',
        'widget_module' => 'number',
        'widget_active' => '1',
      ],
      [
        'field_name' => 'field_test_empty_db_columns',
        'type_name' => 'story',
        'weight' => '33',
        'label' => 'Empty db_columns Field',
        'widget_type' => 'empty_db_columns',
        'widget_settings' => 'a:0:{}',
        'display_settings' => 'a:0:{}',
        'description' => 'An example field with empty db_columns.',
        'widget_module' => 'empty_db_columns',
        'widget_active' => '1',
      ],
    ];
    $tests[0]['source_data']['content_type_story'] = [
      [
        'nid' => 5,
        'vid' => 5,
        'uid' => 5,
        'field_test_four_value' => '3.14159',
      ],
    ];
    $tests[0]['source_data']['system'] = [
      [
        'type' => 'module',
        'name' => 'content',
        'schema_version' => 6001,
        'status' => TRUE,
      ],
    ];
    $tests[0]['source_data']['node'] = [
      [
        'nid' => 1,
        'vid' => 1,
        'type' => 'page',
        'language' => 'en',
        'title' => 'node title 1',
        'uid' => 1,
        'status' => 1,
        'created' => 1279051598,
        'changed' => 1279051598,
        'comment' => 2,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'translate' => 0,
        'tnid' => 0,
      ],
      [
        'nid' => 2,
        'vid' => 2,
        'type' => 'page',
        'language' => 'en',
        'title' => 'node title 2',
        'uid' => 1,
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'translate' => 0,
        'tnid' => 0,
      ],
      [
        'nid' => 5,
        'vid' => 5,
        'type' => 'story',
        'language' => 'en',
        'title' => 'node title 5',
        'uid' => 1,
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'translate' => 0,
        'tnid' => 0,
      ],
      [
        'nid' => 6,
        'vid' => 6,
        'type' => 'story',
        'language' => 'en',
        'title' => 'node title 6',
        'uid' => 1,
        'status' => 1,
        'created' => 1279290909,
        'changed' => 1279308994,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'translate' => 0,
        'tnid' => 6,
      ],
      [
        'nid' => 7,
        'vid' => 7,
        'type' => 'story',
        'language' => 'fr',
        'title' => 'node title 7',
        'uid' => 1,
        'status' => 1,
        'created' => 1279290910,
        'changed' => 1279308995,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'translate' => 0,
        'tnid' => 6,
      ],
    ];
    $tests[0]['source_data']['node_revisions'] = [
      [
        'nid' => 1,
        'vid' => 1,
        'uid' => 2,
        'title' => 'node title 1',
        'body' => 'body for node 1',
        'teaser' => 'teaser for node 1',
        'log' => '',
        'format' => 1,
        'timestamp' => 1279051598,
      ],
      [
        'nid' => 2,
        'vid' => 2,
        'uid' => 2,
        'title' => 'node title 2',
        'body' => 'body for node 2',
        'teaser' => 'teaser for node 2',
        'log' => '',
        'format' => 1,
        'timestamp' => 1279308993,
      ],
      [
        'nid' => 5,
        'vid' => 5,
        'uid' => 2,
        'title' => 'node title 5',
        'body' => 'body for node 5',
        'teaser' => 'body for node 5',
        'log' => '',
        'format' => 1,
        'timestamp' => 1279308993,
      ],
      [
        'nid' => 6,
        'vid' => 6,
        'uid' => 2,
        'title' => 'node title 6',
        'body' => 'body for node 6',
        'teaser' => 'body for node 6',
        'log' => '',
        'format' => 1,
        'timestamp' => 1279308994,
      ],
      [
        'nid' => 7,
        'vid' => 7,
        'uid' => 2,
        'title' => 'node title 7',
        'body' => 'body for node 7',
        'teaser' => 'body for node 7',
        'log' => '',
        'format' => 1,
        'timestamp' => 1279308995,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        // Node fields.
        'nid' => 1,
        'vid' => 1,
        'type' => 'page',
        'language' => 'en',
        'title' => 'node title 1',
        'node_uid' => 1,
        'revision_uid' => 2,
        'status' => 1,
        'created' => 1279051598,
        'changed' => 1279051598,
        'comment' => 2,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 1,
        'translate' => 0,
        // Node revision fields.
        'body' => 'body for node 1',
        'teaser' => 'teaser for node 1',
        'log' => '',
        'timestamp' => 1279051598,
        'format' => 1,
      ],
      [
        // Node fields.
        'nid' => 2,
        'vid' => 2,
        'type' => 'page',
        'language' => 'en',
        'title' => 'node title 2',
        'node_uid' => 1,
        'revision_uid' => 2,
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 2,
        'translate' => 0,
        // Node revision fields.
        'body' => 'body for node 2',
        'teaser' => 'teaser for node 2',
        'log' => '',
        'timestamp' => 1279308993,
        'format' => 1,
      ],
      [
        'nid' => 5,
        'vid' => 5,
        'type' => 'story',
        'language' => 'en',
        'title' => 'node title 5',
        'node_uid' => 1,
        'revision_uid' => 2,
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 5,
        'translate' => 0,
        // Node revision fields.
        'body' => 'body for node 5',
        'teaser' => 'body for node 5',
        'log' => '',
        'timestamp' => 1279308993,
        'format' => 1,
        'field_test_four' => [
          [
            'value' => '3.14159',
            'delta' => 0,
          ],
        ],
      ],
      [
        'nid' => 6,
        'vid' => 6,
        'type' => 'story',
        'language' => 'en',
        'title' => 'node title 6',
        'node_uid' => 1,
        'revision_uid' => 2,
        'status' => 1,
        'created' => 1279290909,
        'changed' => 1279308994,
        'comment' => 0,
        'promote' => 1,
        'moderate' => 0,
        'sticky' => 0,
        'tnid' => 6,
        'translate' => 0,
        // Node revision fields.
        'body' => 'body for node 6',
        'teaser' => 'body for node 6',
        'log' => '',
        'timestamp' => 1279308994,
        'format' => 1,
      ],
    ];

    return $tests;
  }

}
