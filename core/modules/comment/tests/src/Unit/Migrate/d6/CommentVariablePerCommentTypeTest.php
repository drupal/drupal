<?php

namespace Drupal\Tests\comment\Unit\Migrate\d6;

use Drupal\comment\Plugin\migrate\source\d6\CommentVariablePerCommentType;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * @coversDefaultClass \Drupal\comment\Plugin\migrate\source\d6\CommentVariablePerCommentType
 * @group comment
 */
class CommentVariablePerCommentTypeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = CommentVariablePerCommentType::class;

  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd6_comment_variable_per_comment_type',
    ],
  ];

  protected $expectedResults = [
    // Each result will also include a label and description, but those are
    // static values set by the source plugin and don't need to be asserted.
    [
      'comment_type' => 'comment',
    ],
    [
      'comment_type' => 'comment_no_subject',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['node_type'] = [
      [
        'type' => 'page',
      ],
      [
        'type' => 'story',
      ],
    ];
    $this->databaseContents['variable'] = [
      [
        'name' => 'comment_subject_field_page',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_subject_field_story',
        'value' => serialize(0),
      ],
    ];
    parent::setUp();
  }

}
