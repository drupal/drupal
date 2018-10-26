<?php

namespace Drupal\Tests\comment\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests comment variable per comment type source plugin.
 *
 * @covers \Drupal\comment\Plugin\migrate\source\d6\CommentVariablePerCommentType
 * @group comment
 * @group legacy
 */
class CommentVariablePerCommentTypeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['comment', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   *
   * @dataProvider providerSource
   * @requires extension pdo_sqlite
   * @expectedDeprecation CommentVariablePerCommentType is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.x. Use \Drupal\node\Plugin\migrate\source\d6\NodeType instead.
   * @expectedDeprecation CommentVariable is deprecated in Drupal 8.4.x and will be removed before Drupal 9.0.x. Use \Drupal\node\Plugin\migrate\source\d6\NodeType instead.
   */
  public function testSource(array $source_data, array $expected_data, $expected_count = NULL, array $configuration = [], $high_water = NULL) {
    parent::testSource($source_data, $expected_data, $expected_count, $configuration, $high_water);
  }

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['node_type'] = [
      [
        'type' => 'page',
      ],
      [
        'type' => 'story',
      ],
    ];

    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'comment_subject_field_page',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_subject_field_story',
        'value' => serialize(0),
      ],
    ];

    // The expected results.
    // Each result will also include a label and description, but those are
    // static values set by the source plugin and don't need to be asserted.
    $tests[0]['expected_data'] = [
      [
        'comment_type' => 'comment',
      ],
      [
        'comment_type' => 'comment_no_subject',
      ],
    ];

    return $tests;
  }

}
