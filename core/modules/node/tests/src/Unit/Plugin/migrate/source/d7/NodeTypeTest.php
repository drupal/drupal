<?php

/**
 * @file
 * Contains \Drupal\Tests\node\Unit\Plugin\migrate\source\d7\NodeTypeTest.
 */

namespace Drupal\Tests\node\Unit\Plugin\migrate\source\d7;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests D7 node type source plugin.
 *
 * @group node
 */
class NodeTypeTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\node\Plugin\migrate\source\d7\NodeType';

  protected $migrationConfiguration = array(
    'id' => 'test_nodetypes',
    'source' => array(
      'plugin' => 'd7_node_type',
    ),
  );

  protected $expectedResults = array(
    array(
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
    ),
    array(
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
    ),
  );

  /**
   * Prepopulate contents with results.
   */
  protected function setUp() {
    $this->databaseContents['node_type'] = $this->expectedResults;
    $this->databaseContents['variable'] = array(
      array(
        'name' => 'node_options_page',
        'value' => 'a:1:{i:0;s:6:"status";}',
      ),
      array(
        'name' => 'node_options_story',
        'value' => 'a:1:{i:0;s:6:"status";}',
      ),
    );
    $this->databaseContents['field_config_instance'] = array(
      array(
        'entity_type' => 'node',
        'bundle' => 'page',
        'field_name' => 'body',
        'data' => 'a:1:{s:5:"label";s:4:"Body";}',
      ),
      array(
        'entity_type' => 'node',
        'bundle' => 'story',
        'field_name' => 'body',
        'data' => 'a:1:{s:5:"label";s:4:"Body";}',
      )
    );
    parent::setUp();
  }

}
