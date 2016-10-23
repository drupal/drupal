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

    // The expected results.
    $tests[0]['expected_data'] = $tests[0]['source_data']['node_type'];

    return $tests;
  }

}
