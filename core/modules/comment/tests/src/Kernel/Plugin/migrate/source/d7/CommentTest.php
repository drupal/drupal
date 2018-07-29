<?php

namespace Drupal\Tests\comment\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 comment source plugin.
 *
 * @covers \Drupal\comment\Plugin\migrate\source\d7\Comment
 * @group comment
 */
class CommentTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['comment', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['comment'] = [
      [
        'cid' => '1',
        'pid' => '0',
        'nid' => '1',
        'uid' => '1',
        'subject' => 'A comment',
        'hostname' => '::1',
        'created' => '1421727536',
        'changed' => '1421727536',
        'status' => '1',
        'thread' => '01/',
        'name' => 'admin',
        'mail' => '',
        'homepage' => '',
        'language' => 'und',
      ],
    ];
    $tests[0]['source_data']['node'] = [
      [
        'nid' => '1',
        'vid' => '1',
        'type' => 'test_content_type',
        'language' => 'en',
        'title' => 'A Node',
        'uid' => '1',
        'status' => '1',
        'created' => '1421727515',
        'changed' => '1421727515',
        'comment' => '2',
        'promote' => '1',
        'sticky' => '0',
        'tnid' => '0',
        'translate' => '0',
      ],
    ];
    $tests[0]['source_data']['field_config'] = [
      [
        'id' => '1',
        'translatable' => '0',
      ],
      [
        'id' => '2',
        'translatable' => '1',
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'id' => '14',
        'field_id' => '1',
        'field_name' => 'comment_body',
        'entity_type' => 'comment',
        'bundle' => 'comment_node_test_content_type',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
      [
        'id' => '15',
        'field_id' => '2',
        'field_name' => 'subject_field',
        'entity_type' => 'comment',
        'bundle' => 'comment_node_test_content_type',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
    ];
    $tests[0]['source_data']['field_data_comment_body'] = [
      [
        'entity_type' => 'comment',
        'bundle' => 'comment_node_test_content_type',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'und',
        'delta' => '0',
        'comment_body_value' => 'This is a comment',
        'comment_body_format' => 'filtered_html',
      ],
    ];
    $tests[0]['source_data']['field_data_subject_field'] = [
      [
        'entity_type' => 'comment',
        'bundle' => 'comment_node_test_content_type',
        'deleted' => '0',
        'entity_id' => '1',
        'revision_id' => '1',
        'language' => 'und',
        'delta' => '0',
        'subject_field_value' => 'A comment (subject_field)',
        'subject_field_format' => NULL,
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
        'cid' => '1',
        'pid' => '0',
        'nid' => '1',
        'uid' => '1',
        'subject' => 'A comment (subject_field)',
        'hostname' => '::1',
        'created' => '1421727536',
        'changed' => '1421727536',
        'status' => '1',
        'thread' => '01/',
        'name' => 'admin',
        'mail' => '',
        'homepage' => '',
        'language' => 'und',
        'comment_body' => [
          [
            'value' => 'This is a comment',
            'format' => 'filtered_html',
          ],
        ],
      ],
    ];

    return $tests;
  }

}
