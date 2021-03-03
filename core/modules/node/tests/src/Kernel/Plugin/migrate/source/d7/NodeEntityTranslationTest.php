<?php

namespace Drupal\Tests\node\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests Drupal 7 node entity translations source plugin.
 *
 * @covers \Drupal\node\Plugin\migrate\source\d7\NodeEntityTranslation
 *
 * @group node
 */
class NodeEntityTranslationTest extends MigrateSqlSourceTestBase {

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
    $tests[0]['source_data']['entity_translation'] = [
      [
        'entity_type' => 'node',
        'entity_id' => 2,
        'revision_id' => 2,
        'language' => 'en',
        'source' => '',
        'uid' => 1,
        'status' => 1,
        'translate' => 0,
        'created' => 1531343498,
        'changed' => 1531343498,
      ],
      [
        'entity_type' => 'node',
        'entity_id' => 2,
        'revision_id' => 2,
        'language' => 'fr',
        'source' => 'en',
        'uid' => 2,
        'status' => 1,
        'translate' => 1,
        'created' => 1531343508,
        'changed' => 1531343508,
      ],
      [
        'entity_type' => 'node',
        'entity_id' => 2,
        'revision_id' => 2,
        'language' => 'es',
        'source' => 'en',
        'uid' => 1,
        'status' => 0,
        'translate' => 0,
        'created' => 1531343528,
        'changed' => 1531343528,
      ],
      [
        'entity_type' => 'node',
        'entity_id' => 3,
        'revision_id' => 3,
        'language' => 'fr',
        'source' => 'en',
        'uid' => 1,
        'status' => 0,
        'translate' => 0,
        'created' => 1531343528,
        'changed' => 1531343528,
      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => 1,
        'field_name' => 'body',
        'type' => 'text_with_summary',
        'module' => 'text',
        'active' => 1,
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => 1,
        'locked' => 1,
        'data' => 'a:0:{}',
        'cardinality' => 1,
        'translatable' => 1,
        'deleted' => 0,
      ],
      [
        'id' => 2,
        'field_name' => 'title_field',
        'type' => 'text',
        'module' => 'text',
        'active' => 1,
        'storage_type' => 'field_sql_storage',
        'storage_module' => 'field_sql_storage',
        'storage_active' => 1,
        'locked' => 1,
        'data' => 'a:0:{}',
        'cardinality' => 1,
        'translatable' => 1,
        'deleted' => 0,
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => 1,
        'field_id' => 1,
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'article',
        'data' => 'a:0:{}',
        'deleted' => 0,
      ],
      [
        'id' => 2,
        'field_id' => 1,
        'field_name' => 'body',
        'entity_type' => 'node',
        'bundle' => 'page',
        'data' => 'a:0:{}',
        'deleted' => 0,
      ],
      [
        'id' => 3,
        'field_id' => 2,
        'field_name' => 'title_field',
        'entity_type' => 'node',
        'bundle' => 'page',
        'data' => 'a:0:{}',
        'deleted' => 0,
      ],
      [
        'id' => 4,
        'field_id' => 2,
        'field_name' => 'title_field',
        'entity_type' => 'node',
        'bundle' => 'article',
        'data' => 'a:0:{}',
        'deleted' => 0,
      ],
    ];
    $tests[0]['source_data']['field_revision_body'] = [
      [
        'entity_type' => 'node',
        'bundle' => 'article',
        'deleted' => 0,
        'entity_id' => 1,
        'revision_id' => 1,
        'language' => 'en',
        'delta' => 0,
        'body_value' => 'Untranslated body',
        'body_summary' => 'Untranslated summary',
        'body_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => 0,
        'entity_id' => 2,
        'revision_id' => 2,
        'language' => 'en',
        'delta' => 0,
        'body_value' => 'English body',
        'body_summary' => 'English summary',
        'body_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => 0,
        'entity_id' => 2,
        'revision_id' => 2,
        'language' => 'fr',
        'delta' => 0,
        'body_value' => 'French body',
        'body_summary' => 'French summary',
        'body_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => 0,
        'entity_id' => 2,
        'revision_id' => 2,
        'language' => 'es',
        'delta' => 0,
        'body_value' => 'Spanish body',
        'body_summary' => 'Spanish summary',
        'body_format' => 'filtered_html',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'article',
        'deleted' => 0,
        'entity_id' => 3,
        'revision_id' => 3,
        'language' => 'fr',
        'delta' => 0,
        'body_value' => 'French body',
        'body_summary' => 'French summary',
        'body_format' => 'filtered_html',
      ],
    ];
    $tests[0]['source_data']['field_revision_title_field'] = [
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => '0',
        'entity_id' => '2',
        'revision_id' => '2',
        'language' => 'en',
        'delta' => '0',
        'title_field_value' => 'English Source',
        'title_field_format' => NULL,
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => '0',
        'entity_id' => '2',
        'revision_id' => '2',
        'language' => 'fr',
        'delta' => '0',
        'title_field_value' => 'French Translation',
        'title_field_format' => NULL,
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'deleted' => '0',
        'entity_id' => '2',
        'revision_id' => '2',
        'language' => 'es',
        'delta' => '0',
        'title_field_value' => 'Spanish Translation',
        'title_field_format' => NULL,
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'article',
        'deleted' => '0',
        'entity_id' => '3',
        'revision_id' => '3',
        'language' => 'fr',
        'delta' => '0',
        'title_field_value' => 'French Translation',
        'title_field_format' => NULL,
      ],
    ];
    $tests[0]['source_data']['node'] = [
      [
        'nid' => 1,
        'vid' => 1,
        'type' => 'article',
        'language' => 'en',
        'title' => 'Untranslated article',
        'uid' => 1,
        'status' => 1,
        'created' => 1531343456,
        'changed' => 1531343456,
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
        'title' => 'Translated page',
        'uid' => 1,
        'status' => 1,
        'created' => 1531343528,
        'changed' => 1531343528,
        'comment' => 1,
        'promote' => 0,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ],
      [
        'nid' => 3,
        'vid' => 3,
        'type' => 'article',
        'language' => 'en',
        'title' => 'Translated article',
        'uid' => 1,
        'status' => 1,
        'created' => 1531343456,
        'changed' => 1531343456,
        'comment' => 2,
        'promote' => 0,
        'sticky' => 0,
        'tnid' => 0,
        'translate' => 0,
      ],
    ];
    $tests[0]['source_data']['node_revision'] = [
      [
        'nid' => 1,
        'vid' => 1,
        'uid' => 1,
        'title' => 'Untranslated article',
        'log' => '',
        'timestamp' => 1531343456,
        'status' => 1,
        'comment' => 2,
        'promote' => 1,
        'sticky' => 0,
      ],
      [
        'nid' => 2,
        'vid' => 2,
        'uid' => 1,
        'title' => 'Translated page',
        'log' => '',
        'timestamp' => 1531343528,
        'status' => 1,
        'comment' => 1,
        'promote' => 0,
        'sticky' => 0,
      ],
      [
        'nid' => 3,
        'vid' => 3,
        'uid' => 1,
        'title' => 'Translated article',
        'log' => '',
        'timestamp' => 1531343528,
        'status' => 1,
        'comment' => 1,
        'promote' => 0,
        'sticky' => 0,
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
        'entity_type' => 'node',
        'entity_id' => 2,
        'revision_id' => 2,
        'language' => 'fr',
        'source' => 'en',
        'uid' => 2,
        'status' => 1,
        'translate' => 1,
        'created' => 1531343508,
        'changed' => 1531343508,
        'type' => 'page',
        'title' => 'French Translation',
        'promote' => 0,
        'sticky' => 0,
        'log' => '',
        'timestamp' => 1531343528,
        'revision_uid' => 1,
        'body' => [
          [
            'value' => 'French body',
            'summary' => 'French summary',
            'format' => 'filtered_html',
          ],
        ],
      ],
      [
        'entity_type' => 'node',
        'entity_id' => 2,
        'revision_id' => 2,
        'language' => 'es',
        'source' => 'en',
        'uid' => 1,
        'status' => 0,
        'translate' => 0,
        'created' => 1531343528,
        'changed' => 1531343528,
        'type' => 'page',
        'title' => 'Spanish Translation',
        'promote' => 0,
        'sticky' => 0,
        'log' => '',
        'timestamp' => 1531343528,
        'revision_uid' => 1,
        'body' => [
          [
            'value' => 'Spanish body',
            'summary' => 'Spanish summary',
            'format' => 'filtered_html',
          ],
        ],
      ],
    ];

    // Do an automatic count.
    $tests[0]['expected_count'] = NULL;

    // Set up source plugin configuration.
    $tests[0]['configuration'] = [
      'node_type' => 'page',
    ];

    // Tests retrieval translations of article and page content types.
    $tests[1] = $tests[0];
    $tests[1]['configuration'] = [
      'node_type' => ['article', 'page'],
    ];
    $tests[1]['expected_data'][] = [
      'entity_type' => 'node',
      'entity_id' => 3,
      'revision_id' => 3,
      'language' => 'fr',
      'source' => 'en',
      'uid' => 1,
      'status' => 0,
      'translate' => 0,
      'created' => 1531343528,
      'changed' => 1531343528,
      'type' => 'article',
      'title' => 'French Translation',
      'promote' => 0,
      'sticky' => 0,
      'log' => '',
      'timestamp' => 1531343528,
      'revision_uid' => 1,
      'body' => [
        [
          'value' => 'French body',
          'summary' => 'French summary',
          'format' => 'filtered_html',
        ],
      ],
    ];

    // Tests retrieval of entity translations without configuration.
    $tests[2] = $tests[1];
    $tests[2]['configuration'] = [];

    return $tests;
  }

}
