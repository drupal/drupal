<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeEntityViewModeAlterTest.
 */

namespace Drupal\node\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests changing view modes for nodes.
 */
class NodeEntityViewModeAlterTest extends NodeTestBase {

  /**
   * Enable dummy module that implements hook_node_view().
   */
  public static $modules = array('node_test');

  public static function getInfo() {
    return array(
      'name' => 'Node entity view mode',
      'description' => 'Test changing view mode.',
      'group' => 'Node'
    );
  }

  /**
   * Create a "Basic page" node and verify its consistency in the database.
   */
  function testNodeViewModeChange() {
    $web_user = $this->drupalCreateUser(array('create page content', 'edit own page content'));
    $this->drupalLogin($web_user);

    // Create a node.
    $edit = array();
    $langcode = Language::LANGCODE_NOT_SPECIFIED;
    $edit["title"] = $this->randomName(8);
    $edit["body[$langcode][0][value]"] = t('Data that should appear only in the body for the node.');
    $edit["body[$langcode][0][summary]"] = t('Extra data that should appear only in the teaser for the node.');
    $this->drupalPost('node/add/page', $edit, t('Save'));

    $node = $this->drupalGetNodeByTitle($edit["title"]);

    // Set the flag to alter the view mode and view the node.
    \Drupal::state()->set('node_test_change_view_mode', 'teaser');
    $this->drupalGet('node/' . $node->nid);

    // Check that teaser mode is viewed.
    $this->assertText('Extra data that should appear only in the teaser for the node.', 'Teaser text present');
    // Make sure body text is not present.
    $this->assertNoText('Data that should appear only in the body for the node.', 'Body text not present');

    // Test that the correct build mode has been set.
    $build = node_view($node);
    $this->assertEqual($build['#view_mode'], 'teaser', 'The view mode has correctly been set to teaser.');
  }
}
