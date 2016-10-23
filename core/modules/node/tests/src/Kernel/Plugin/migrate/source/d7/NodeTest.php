<?php

namespace Drupal\Tests\node\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 node source plugin.
 *
 * @covers \Drupal\node\Plugin\migrate\source\d7\Node
 *
 * @group node
 */
class NodeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['node'] = [
      [
        // Node fields.
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
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ],
      [
        // Node fields.
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
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ],
      [
        // Node fields.
        'nid' => 5,
        'vid' => 5,
        'type' => 'article',
        'language' => 'en',
        'title' => 'node title 5',
        'uid' => 1,
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ],

    ];
    $tests[0]['source_data']['node_revision'] = [
      [
        // Node fields.
        'nid' => 1,
        'vid' => 1,
        'uid' => 2,
        'title' => 'node title 1',
        'log' => '',
        'timestamp' => 1279051598,
        'status' => 1,
        'comment' => 2,
        'promote' => 1,
        'sticky' => 0,
      ],
      [
        // Node fields.
        'nid' => 2,
        'vid' => 2,
        'uid' => 2,
        'title' => 'node title 2',
        'log' => '',
        'timestamp' => 1279308993,
        'status' => 1,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
      ],
      [
        // Node fields.
        'nid' => 5,
        'vid' => 5,
        'uid' => 2,
        'title' => 'node title 5',
        'log' => '',
        'timestamp' => 1279308993,
        'status' => 1,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'page',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
      [
        'id' => '2',
        'field_id' => '2',
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'article',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_revision_body'] = [
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'en',
        'delta' => '0',
        'body_value' => 'Foobaz',
        'body_summary' => '',
        'body_format' => 'filtered_html',
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
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
        'log' => '',
        'timestamp' => 1279051598,
        'body' => [
          [
            'value' => 'Foobaz',
            'summary' => '',
            'format' => 'filtered_html',
          ],
        ],
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
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
        'log' => '',
        'timestamp' => 1279308993,
      ],
      [
        // Node fields.
        'nid' => 5,
        'vid' => 5,
        'type' => 'article',
        'language' => 'en',
        'title' => 'node title 5',
        'node_uid' => 1,
        'revision_uid' => 2,
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
        'log' => '',
        'timestamp' => 1279308993,
      ],
    ];

    return $tests;
  }

}
