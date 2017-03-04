<?php

namespace Drupal\Tests\comment\Unit\Migrate\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 comment type source plugin.
 *
 * @group comment
 */
class CommentTypeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\comment\Plugin\migrate\source\d7\CommentType';

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd7_comment_type',
    ],
  ];

  protected $expectedResults = [
    [
      'bundle' => 'comment_node_article',
      'node_type' => 'article',
      'default_mode' => '1',
      'per_page' => '50',
      'anonymous' => '0',
      'form_location' => '1',
      'preview' => '0',
      'subject' => '1',
      'label' => 'Article comment',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['node_type'] = [
      [
        'type' => 'article',
        'name' => 'Article',
        'base' => 'node_content',
        'module' => 'node',
        'description' => 'Use <em>articles</em> for time-sensitive content like news, press releases or blog posts.',
        'help' => 'Help text for articles',
        'has_title' => '1',
        'title_label' => 'Title',
        'custom' => '1',
        'modified' => '1',
        'locked' => '0',
        'disabled' => '0',
        'orig_type' => 'article',
      ],
    ];
    $this->databaseContents['field_config_instance'] = [
      [
        'id' => '14',
        'field_id' => '1',
        'field_name' => 'comment_body',
        'entity_type' => 'comment',
        'bundle' => 'comment_node_article',
        'data' => 'a:0:{}',
        'deleted' => '0',
      ],
    ];
    $this->databaseContents['variable'] = [
      [
        'name' => 'comment_default_mode_article',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_per_page_article',
        'value' => serialize(50),
      ],
      [
        'name' => 'comment_anonymous_article',
        'value' => serialize(0),
      ],
      [
        'name' => 'comment_form_location_article',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_preview_article',
        'value' => serialize(0),
      ],
      [
        'name' => 'comment_subject_article',
        'value' => serialize(1),
      ],
    ];
    parent::setUp();
  }

}
