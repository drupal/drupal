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
  protected static $modules = ['node', 'user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // Test retrieval of article and page content types when configuration
    // key 'node_type' is not set.
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
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
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
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ],
      [
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
      [
        'nid' => 6,
        'vid' => 6,
        'type' => 'article',
        'language' => 'en',
        'title' => 'node title 5',
        'uid' => 1,
        'status' => 1,
        'created' => 1279291908,
        'changed' => 1279309993,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 6,
        'translate' => 0,
      ],
      [
        'nid' => 7,
        'vid' => 7,
        'type' => 'article',
        'language' => 'fr',
        'title' => 'fr - node title 5',
        'uid' => 1,
        'status' => 1,
        'created' => 1279292908,
        'changed' => 1279310993,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 6,
        'translate' => 0,
      ],
    ];
    $tests[0]['source_data']['node_revision'] = [
      [
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
      [
        'nid' => 6,
        'vid' => 6,
        'uid' => 1,
        'title' => 'node title 5',
        'log' => '',
        'timestamp' => 1279309993,
        'status' => 1,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,

      ],
      [
        'nid' => 7,
        'vid' => 7,
        'uid' => 1,
        'title' => 'fr - node title 5',
        'log' => '',
        'timestamp' => 1279310993,
        'status' => 1,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '2',
        'translatable' => '0',
      ],
      [
        'id' => '3',
        'translatable' => '1',
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
        'id' => '3',
        'field_id' => '2',
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'article',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
      [
        'id' => '4',
        'field_id' => '3',
        'field_name' => 'title_field',
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
        'body_value' => 'Foo',
        'body_summary' => '',
        'body_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => '0',
        'entity_id' => '2',
        'revision_id' => '2',
        'language' => 'en',
        'delta' => '0',
        'body_value' => 'body 2',
        'body_summary' => '',
        'body_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => '0',
        'entity_id' => '5',
        'revision_id' => '5',
        'language' => 'en',
        'delta' => '0',
        'body_value' => 'body 5',
        'body_summary' => '',
        'body_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => '0',
        'entity_id' => '6',
        'revision_id' => '6',
        'language' => 'en',
        'delta' => '0',
        'body_value' => 'body 6',
        'body_summary' => '',
        'body_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => '0',
        'entity_id' => '7',
        'revision_id' => '7',
        'language' => 'fr',
        'delta' => '0',
        'body_value' => 'fr - body 6',
        'body_summary' => '',
        'body_format' => 'filtered_html',
      ],
    ];
    $tests[0]['source_data']['field_revision_title_field'] = [
      [
        'entity_type' => 'node',
        'bundle' => 'article',
        'deleted' => '0',
        'entity_id' => '5',
        'revision_id' => '5',
        'language' => 'en',
        'delta' => '0',
        'title_field_value' => 'node title 5 (title_field)',
        'title_field_format' => NULL,
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'article',
        'deleted' => '0',
        'entity_id' => '6',
        'revision_id' => '6',
        'language' => 'en',
        'delta' => '0',
        'title_field_value' => 'node title 5 (title_field)',
        'title_field_format' => NULL,
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'article',
        'deleted' => '0',
        'entity_id' => '7',
        'revision_id' => '7',
        'language' => 'en',
        'delta' => '0',
        'title_field_value' => 'node title 5 (title_field)',
        'title_field_format' => NULL,
      ],
    ];
    $tests[0]['source_data']['system'] = [
      [
        'name' => 'title',
        'type' => 'module',
        'status' => 1,
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
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
        'tnid' => 1,
        'translate' => 0,
        'log' => '',
        'timestamp' => 1279051598,
        'body' => [
          [
            'value' => 'Foo',
            'summary' => '',
            'format' => 'filtered_html',
          ],
        ],
      ],
      [
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
        'tnid' => 2,
        'translate' => 0,
        'log' => '',
        'timestamp' => 1279308993,
        'body' => [
          [
            'value' => 'body 2',
            'summary' => '',
            'format' => 'filtered_html',
          ],
        ],
      ],
      [
        'nid' => 5,
        'vid' => 5,
        'type' => 'article',
        'language' => 'en',
        'title' => 'node title 5 (title_field)',
        'node_uid' => 1,
        'revision_uid' => 2,
        'status' => 1,
        'created' => 1279290908,
        'changed' => 1279308993,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 5,
        'translate' => 0,
        'log' => '',
        'timestamp' => 1279308993,
        'body' => [
          [
            'value' => 'body 5',
            'summary' => '',
            'format' => 'filtered_html',
          ],
        ],
      ],
      [
        'nid' => 6,
        'vid' => 6,
        'type' => 'article',
        'language' => 'en',
        'title' => 'node title 5 (title_field)',
        'node_uid' => 1,
        'revision_uid' => 1,
        'status' => 1,
        'created' => 1279291908,
        'changed' => 1279309993,
        'comment' => 0,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 6,
        'translate' => 0,
        'log' => '',
        'timestamp' => 1279309993,
        'body' => [
          [
            'value' => 'body 6',
            'summary' => '',
            'format' => 'filtered_html',
          ],
        ],
      ],
    ];

    // The source data with a correct 'entity_translation' table.
    $tests[1]['source_data']['entity_translation'] = [
      [
        'entity_type' => 'node',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'en',
        'source' => '',
        'uid' => 1,
        'status' => 1,
        'translate' => 0,
        'created' => 1279051598,
        'changed' => 1279051598,
      ],
      [
        'entity_type' => 'node',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'fr',
        'source' => 'en',
        'uid' => 1,
        'status' => 1,
        'translate' => 0,
        'created' => 1279051598,
        'changed' => 1279051598,
      ],
    ];
    $tests[1]['source_data']['field_config'] = [
      [
        'id' => '1',
        'translatable' => '1',
      ],
    ];
    $tests[1]['source_data']['field_config_instance'] = [
      [
        'id' => '1',
        'field_id' => '1',
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'page',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
    ];
    $tests[1]['source_data']['field_revision_body'] = [
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'en',
        'delta' => '0',
        'body_value' => 'English body',
        'body_summary' => '',
        'body_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'fr',
        'delta' => '0',
        'body_value' => 'French body',
        'body_summary' => '',
        'body_format' => 'filtered_html',
      ],
    ];
    $tests[1]['source_data']['node'] = [
      [
        'nid' => 1,
        'vid' => 1,
        'type' => 'page',
        'language' => 'en',
        'title' => 'Node Title',
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
    ];
    $tests[1]['source_data']['node_revision'] = [
      [
        'nid' => 1,
        'vid' => 1,
        'uid' => 1,
        'title' => 'Node Title',
        'log' => '',
        'timestamp' => 1279051598,
        'status' => 1,
        'comment' => 2,
        'promote' => 1,
        'sticky' => 0,
      ],
    ];
    $tests[1]['source_data']['variable'] = [
      [
        'name' => 'entity_translation_entity_types',
        'value' => 'a:4:{s:7:"comment";i:0;s:4:"node";s:4:"node";s:13:"taxonomy_term";i:0;s:4:"user";i:0;}',
      ],
      [
        'name' => 'language_content_type_page',
        'value' => 's:1:"4";',
      ],
    ];

    // The expected results with a correct 'entity_translation' table.
    // entity_translation table.
    $tests[1]['expected_data'] = [
      [
        'nid' => 1,
        'vid' => 1,
        'type' => 'page',
        'language' => 'en',
        'title' => 'Node Title',
        'node_uid' => 1,
        'revision_uid' => 1,
        'status' => 1,
        'created' => 1279051598,
        'changed' => 1279051598,
        'comment' => 2,
        'promote' => 1,
        'sticky' => 0,
        'tnid' => 1,
        'translate' => 0,
        'log' => '',
        'timestamp' => 1279051598,
        'body' => [
          [
            'value' => 'English body',
            'summary' => '',
            'format' => 'filtered_html',
          ],
        ],
      ],
    ];

    // Repeat the previous test with an incorrect 'entity_translation' table
    // where the row with the empty 'source' property is missing.
    $tests[2]['source_data'] = $tests[1]['source_data'];
    $tests[2]['source_data']['entity_translation'] = [
      [
        'entity_type' => 'node',
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'fr',
        'source' => 'en',
        'uid' => 1,
        'status' => 1,
        'translate' => 0,
        'created' => 1279051598,
        'changed' => 1279051598,
      ],
    ];
    $tests[2]['expected_data'] = $tests[1]['expected_data'];

    // Tests retrieval of only the page content type.
    $tests[3]['source_data'] = $tests[0]['source_data'];

    // The expected results.
    $tests[3]['expected_data'] = [
      [
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
        'tnid' => 1,
        'translate' => 0,
        'log' => '',
        'timestamp' => 1279051598,
        'body' => [
          [
            'value' => 'Foo',
            'summary' => '',
            'format' => 'filtered_html',
          ],
        ],
      ],
      [
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
        'tnid' => 2,
        'translate' => 0,
        'log' => '',
        'timestamp' => 1279308993,
        'body' => [
          [
            'value' => 'body 2',
            'summary' => '',
            'format' => 'filtered_html',
          ],
        ],
      ],
    ];

    $tests[3]['expected_count'] = NULL;
    $tests[3]['configuration'] = [
      'node_type' => 'page',
    ];

    // Tests retrieval of article and page content types.
    $tests[4] = $tests[3];
    $tests[4]['configuration'] = [
      'node_type' => ['article', 'page'],
    ];
    $tests[4]['expected_data'] = $tests[0]['expected_data'];

    return $tests;
  }

}
