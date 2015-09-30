<?php

/**
 * @file
 * Contains \Drupal\Tests\comment\Unit\Migrate\d6\CommentVariablePerCommentTypeTest.
 */

namespace Drupal\Tests\comment\Unit\Migrate\d6;

use Drupal\comment\Plugin\migrate\source\d6\CommentVariablePerCommentType;
use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * @coversDefaultClass \Drupal\comment\Plugin\migrate\source\d6\CommentVariablePerCommentType
 * @group comment
 */
class CommentVariablePerCommentTypeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = CommentVariablePerCommentType::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_comment_variable_per_comment_type',
    ),
  );

  protected $expectedResults = array(
    // Each result will also include a label and description, but those are
    // static values set by the source plugin and don't need to be asserted.
    array(
      'comment_type' => 'comment_no_subject',
    ),
    array(
      'comment_type' => 'comment',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['node_type'] = array(
      array(
        'type' => 'page',
      ),
      array(
        'type' => 'story',
      ),
    );
    $this->databaseContents['variable'] = array(
      array(
        'name' => 'comment_subject_field_page',
        'value' => serialize(1),
      ),
      array(
        'name' => 'comment_subject_field_story',
        'value' => serialize(0),
      ),
    );
    parent::setUp();
  }

}
