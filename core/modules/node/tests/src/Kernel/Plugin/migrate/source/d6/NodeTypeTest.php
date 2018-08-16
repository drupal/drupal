<?php

namespace Drupal\Tests\node\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D6 node type source plugin.
 *
 * @covers \Drupal\node\Plugin\migrate\source\d6\NodeType
 *
 * @group node
 */
class NodeTypeTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['node_type'] = [
      [
        'type' => 'page',
        'name' => 'Page',
        'module' => 'node',
        'description' => 'A <em>page</em>, similar in form to a <em>story</em>, is a simple method for creating and displaying information that rarely changes, such as an "About us" section of a website. By default, a <em>page</em> entry does not allow visitor comments and is not featured on the site\'s initial home page.',
        'help' => '',
        'title_label' => 'Title',
        'has_body' => 1,
        'body_label' => 'Body',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'orig_type' => 'page',
      ],
      [
        'type' => 'story',
        'name' => 'Story',
        'module' => 'node',
        'description' => 'A <em>story</em>, similar in form to a <em>page</em>, is ideal for creating and displaying content that informs or engages website visitors. Press releases, site announcements, and informal blog-like entries may all be created with a <em>story</em> entry. By default, a <em>story</em> entry is automatically featured on the site\'s initial home page, and provides the ability to post comments.',
        'help' => '',
        'title_label' => 'Title',
        'has_body' => 1,
        'body_label' => 'Body',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'orig_type' => 'story',
      ],
    ];
    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'comment_anonymous_page',
        'value' => 'i:0;',
      ],
      [
        'name' => 'comment_anonymous_story',
        'value' => 'i:1;',
      ],
      [
        'name' => 'comment_default_mode_page',
        'value' => 'i:0;',
      ],
      [
        'name' => 'comment_default_mode_story',
        'value' => 'i:1;',
      ],
      [
        'name' => 'comment_default_per_page_page',
        'value' => 's:2:"10";',
      ],
      [
        'name' => 'comment_default_per_page_story',
        'value' => 's:2:"20";',
      ],
      [
        'name' => 'comment_form_location_page',
        'value' => 'i:0;',
      ],
      [
        'name' => 'comment_form_location_story',
        'value' => 'i:1;',
      ],
      [
        'name' => 'comment_page',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'comment_preview_page',
        'value' => 's:1:"0";',
      ],
      [
        'name' => 'comment_preview_story',
        'value' => 's:1:"1";',
      ],
      [
        'name' => 'comment_story',
        'value' => 's:1:"1";',
      ],
      [
        'name' => 'comment_subject_field_page',
        'value' => 'i:0;',
      ],
      [
        'name' => 'comment_subject_field_story',
        'value' => 'i:1;',
      ],
      [
        'name' => 'node_options_page',
        'value' => 'a:1:{i:0;s:6:"status";}',
      ],
      [
        'name' => 'node_options_story',
        'value' => 'a:1:{i:0;s:6:"status";}',
      ],
    ];
    $tests[0]['source_data']['system'] = [
      [
        'type' => 'module',
        'name' => 'comment',
        'status' => '1',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'type' => 'page',
        'name' => 'Page',
        'module' => 'node',
        'description' => 'A <em>page</em>, similar in form to a <em>story</em>, is a simple method for creating and displaying information that rarely changes, such as an "About us" section of a website. By default, a <em>page</em> entry does not allow visitor comments and is not featured on the site\'s initial home page.',
        'help' => '',
        'title_label' => 'Title',
        'has_body' => 1,
        'body_label' => 'Body',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'orig_type' => 'page',
        'options' => [
          'promote' => FALSE,
          'sticky' => FALSE,
          'status' => TRUE,
          'revision' => FALSE,
        ],
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
        'module' => 'node',
        'description' => 'A <em>story</em>, similar in form to a <em>page</em>, is ideal for creating and displaying content that informs or engages website visitors. Press releases, site announcements, and informal blog-like entries may all be created with a <em>story</em> entry. By default, a <em>story</em> entry is automatically featured on the site\'s initial home page, and provides the ability to post comments.',
        'help' => '',
        'title_label' => 'Title',
        'has_body' => 1,
        'body_label' => 'Body',
        'min_word_count' => 0,
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'orig_type' => 'story',
        'options' => [
          'promote' => FALSE,
          'sticky' => FALSE,
          'status' => TRUE,
          'revision' => FALSE,
        ],
        'comment' => 1,
        'comment_default_mode' => 1,
        'comment_default_per_page' => '20',
        'comment_anonymous' => 1,
        'comment_subject_field' => 1,
        'comment_preview' => 1,
        'comment_form_location' => 1,
      ],
    ];

    return $tests;
  }

}
