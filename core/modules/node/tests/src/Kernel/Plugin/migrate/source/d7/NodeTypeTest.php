<?php

namespace Drupal\Tests\node\Kernel\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests D7 node type source plugin.
 *
 * @covers \Drupal\node\Plugin\migrate\source\d7\NodeType
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
        'base' => 'node',
        'description' => 'A <em>page</em>, similar in form to a <em>story</em>, is a simple method for creating and displaying information that rarely changes, such as an "About us" section of a website. By default, a <em>page</em> entry does not allow visitor comments and is not featured on the site\'s initial home page.',
        'help' => '',
        'title_label' => 'Title',
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'disabled' => 0,
        'orig_type' => 'page',
      ],
      [
        'type' => 'story',
        'name' => 'Story',
        'base' => 'node',
        'description' => 'A <em>story</em>, similar in form to a <em>page</em>, is ideal for creating and displaying content that informs or engages website visitors. Press releases, site announcements, and informal blog-like entries may all be created with a <em>story</em> entry. By default, a <em>story</em> entry is automatically featured on the site\'s initial home page, and provides the ability to post comments.',
        'help' => '',
        'title_label' => 'Title',
        'custom' => 1,
        'modified' => 0,
        'locked' => 0,
        'disabled' => 0,
        'orig_type' => 'story',
      ],
    ];
    $tests[0]['source_data']['variable'] = [
      [
        'name' => 'node_options_page',
        'value' => 'a:1:{i:0;s:6:"status";}',
      ],
      [
        'name' => 'node_options_story',
        'value' => 'a:1:{i:0;s:6:"status";}',
      ],
    ];
    $tests[0]['source_data']['field_config_instance'] = [
      [
        'entity_type' => 'node',
        'bundle' => 'page',
        'field_name' => 'body',
        'data' => 'a:1:{s:5:"label";s:4:"Body";}',
      ],
      [
        'entity_type' => 'node',
        'bundle' => 'story',
        'field_name' => 'body',
        'data' => 'a:1:{s:5:"label";s:4:"Body";}',
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = $tests[0]['source_data']['node_type'];

    return $tests;
  }

}
