<?php

namespace Drupal\node\Tests;

use Drupal\Core\Cache\Cache;

/**
 * Tests changing view modes for nodes.
 *
 * @group node
 */
class NodeEntityViewModeAlterTest extends NodeTestBase {

  /**
   * Enable dummy module that implements hook_ENTITY_TYPE_view() for nodes.
   */
  public static $modules = ['node_test'];

  /**
   * Create a "Basic page" node and verify its consistency in the database.
   */
  public function testNodeViewModeChange() {
    $web_user = $this->drupalCreateUser(['create page content', 'edit own page content']);
    $this->drupalLogin($web_user);

    // Create a node.
    $edit = [];
    $edit['title[0][value]'] = $this->randomMachineName(8);
    $edit['body[0][value]'] = t('Data that should appear only in the body for the node.');
    $edit['body[0][summary]'] = t('Extra data that should appear only in the teaser for the node.');
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Set the flag to alter the view mode and view the node.
    \Drupal::state()->set('node_test_change_view_mode', 'teaser');
    Cache::invalidateTags(['rendered']);
    $this->drupalGet('node/' . $node->id());

    // Check that teaser mode is viewed.
    $this->assertText('Extra data that should appear only in the teaser for the node.', 'Teaser text present');
    // Make sure body text is not present.
    $this->assertNoText('Data that should appear only in the body for the node.', 'Body text not present');

    // Test that the correct build mode has been set.
    $build = $this->drupalBuildEntityView($node);
    $this->assertEqual($build['#view_mode'], 'teaser', 'The view mode has correctly been set to teaser.');
  }

}
