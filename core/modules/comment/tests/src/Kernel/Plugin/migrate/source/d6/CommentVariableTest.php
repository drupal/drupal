<?php

namespace Drupal\Tests\comment\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests d6_comment_variable source plugin.
 *
 * @covers \Drupal\comment\Plugin\migrate\source\d6\CommentVariable
 * @group comment
 */
class CommentVariableTest extends MigrateSqlSourceTestBase {

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
    ];

    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'comment_page',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_default_mode_page',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_default_order_page',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_default_per_page_page',
        'value' => serialize(50),
      ],
      [
        'name' => 'comment_controls_page',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_anonymous_page',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_subject_field_page',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_preview_page',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_form_location_page',
        'value' => serialize(1),
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'comment' => '1',
        'comment_default_mode' => '1',
        'comment_default_order' => '1',
        'comment_default_per_page' => '50',
        'comment_controls' => '1',
        'comment_anonymous' => '1',
        'comment_subject_field' => '1',
        'comment_preview' => '1',
        'comment_form_location' => '1',
        'node_type' => 'page',
        'comment_type' => 'comment',
      ],
    ];

    return $tests;
  }

}
