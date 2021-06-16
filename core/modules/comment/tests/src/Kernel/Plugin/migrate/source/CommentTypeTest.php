<?php

namespace Drupal\Tests\comment\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the comment type source plugin.
 *
 * @covers \Drupal\comment\Plugin\migrate\source\CommentType
 *
 * @group comment
 */
class CommentTypeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'comment', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $node_type_rows = [
      [
        'type' => 'page',
        'name' => 'Page',
      ],
      [
        'type' => 'story',
        'name' => 'Story',
      ],
    ];
    $comment_variable_rows = [
      [
        'name' => 'comment_anonymous_page',
        'value' => serialize(0),
      ],
      [
        'name' => 'comment_anonymous_story',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_default_mode_page',
        'value' => serialize(0),
      ],
      [
        'name' => 'comment_default_mode_story',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_default_per_page_page',
        'value' => serialize('10'),
      ],
      [
        'name' => 'comment_default_per_page_story',
        'value' => serialize('20'),
      ],
      [
        'name' => 'comment_form_location_page',
        'value' => serialize(0),
      ],
      [
        'name' => 'comment_form_location_story',
        'value' => serialize(1),
      ],
      [
        'name' => 'comment_page',
        'value' => serialize('0'),
      ],
      [
        'name' => 'comment_preview_page',
        'value' => serialize('0'),
      ],
      [
        'name' => 'comment_preview_story',
        'value' => serialize('1'),
      ],
      [
        'name' => 'comment_story',
        'value' => serialize('1'),
      ],
      [
        'name' => 'comment_subject_field_page',
        'value' => serialize(0),
      ],
      [
        'name' => 'comment_subject_field_story',
        'value' => serialize(1),
      ],
    ];

    return [
      'Node and comment enabled, two node types' => [
        'source_data' => [
          'node_type' => $node_type_rows,
          'variable' => $comment_variable_rows,
        ],
        'expected_data' => [
          [
            'type' => 'page',
            'name' => 'Page',
            'comment' => 0,
            'comment_default_mode' => 0,
            'comment_default_per_page' => '10',
            'comment_anonymous' => 0,
            'comment_subject_field' => 0,
            'comment_preview' => 0,
            'comment_form_location' => 0,
          ],
          [
            'type' => 'story',
            'name' => 'Story',
            'comment' => 1,
            'comment_default_mode' => 1,
            'comment_default_per_page' => '20',
            'comment_anonymous' => 1,
            'comment_subject_field' => 1,
            'comment_preview' => 1,
            'comment_form_location' => 1,
          ],
        ],
      ],
      'Node and comment enabled, two node types, no comment variables' => [
        'source_data' => [
          'node_type' => $node_type_rows,
          'variable' => [
            [
              'name' => 'css_js_query_string',
              'value' => serialize('foobar'),
            ],
          ],
        ],
        'expected_data' => [
          [
            'type' => 'page',
            'name' => 'Page',
            'comment' => NULL,
            'comment_default_mode' => NULL,
            'comment_default_per_page' => NULL,
            'comment_anonymous' => NULL,
            'comment_subject_field' => NULL,
            'comment_preview' => NULL,
            'comment_form_location' => NULL,
          ],
          [
            'type' => 'story',
            'name' => 'Story',
            'comment' => NULL,
            'comment_default_mode' => NULL,
            'comment_default_per_page' => NULL,
            'comment_anonymous' => NULL,
            'comment_subject_field' => NULL,
            'comment_preview' => NULL,
            'comment_form_location' => NULL,
          ],
        ],
      ],
    ];
  }

}
