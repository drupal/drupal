<?php

namespace Drupal\Tests\comment\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests comment variable per comment type source plugin.
 *
 * @covers \Drupal\comment\Plugin\migrate\source\d6\CommentVariablePerCommentType
 * @group comment
 */
class CommentVariablePerCommentTypeTest extends MigrateSqlSourceTestBase {

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
