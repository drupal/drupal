<?php

namespace Drupal\Tests\comment\Unit\Migrate\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 comment source plugin.
 *
 * @group comment
 */
class CommentTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\comment\Plugin\migrate\source\d7\Comment';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_comment',
    ],
  ];

  protected $expectedResults = [
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
      'comment_body' => [
        [
          'value' => 'This is a comment',
          'format' => 'filtered_html',
        ],
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['comment'] = $this->expectedResults;
    unset($this->databaseContents['comment'][0]['comment_body']);

    $this->databaseContents['node'] = [
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
    $this->databaseContents['field_config_instance'] = [
      [
        'id' => '14',
        'field_id' => '1',
        'field_name' => 'comment_body',
        'entity_type' => 'comment',
        'bundle' => 'comment_node_test_content_type',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
    ];
    $this->databaseContents['field_data_comment_body'] = [
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
    parent::setUp();
  }

}
