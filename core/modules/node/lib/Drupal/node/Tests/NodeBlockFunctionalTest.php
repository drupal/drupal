<?php

/**
 * @file
 * Definition of Drupal\node\Tests\NodeBlockFunctionalTest.
 */

namespace Drupal\node\Tests;

/**
 * Functional tests for the node module blocks.
 */
class NodeBlockFunctionalTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block');

  public static function getInfo() {
    return array(
      'name' => 'Node blocks',
      'description' => 'Test node block functionality.',
      'group' => 'Node',
    );
  }

  function setUp() {
    parent::setUp();

    // Create users and test node.
    $this->admin_user = $this->drupalCreateUser(array('administer content types', 'administer nodes', 'administer blocks'));
    $this->web_user = $this->drupalCreateUser(array('access content', 'create article content'));
  }

  /**
   * Tests the recent comments block.
   */
  function testRecentNodeBlock() {
    $this->drupalLogin($this->admin_user);

    // Disallow anonymous users to view content.
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access content' => FALSE,
    ));

    // Set the block to a region to confirm block is available.
    $edit = array(
      'blocks[node_recent][region]' => 'sidebar_first',
    );
    $this->drupalPost('admin/structure/block', $edit, t('Save blocks'));
    $this->assertText(t('The block settings have been updated.'), 'Block saved to first sidebar region.');

    // Set block title and variables.
    $block = array(
      'title' => $this->randomName(),
      'node_recent_block_count' => 2,
    );
    $this->drupalPost('admin/structure/block/manage/node/recent/configure', $block, t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), 'Block saved.');

    // Test that block is not visible without nodes
    $this->drupalGet('');
    $this->assertText(t('No content available.'), 'Block with "No content available." found.');

    // Add some test nodes.
    $default_settings = array('uid' => $this->web_user->uid, 'type' => 'article');
    $node1 = $this->drupalCreateNode($default_settings);
    $node2 = $this->drupalCreateNode($default_settings);
    $node3 = $this->drupalCreateNode($default_settings);

    // Change the changed time for node so that we can test ordering.
    db_update('node')
      ->fields(array(
        'changed' => $node1->changed + 100,
      ))
      ->condition('nid', $node2->nid)
      ->execute();
    db_update('node')
      ->fields(array(
        'changed' => $node1->changed + 200,
      ))
      ->condition('nid', $node3->nid)
      ->execute();

    // Test that a user without the 'access content' permission cannot
    // see the block.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoText($block['title'], 'Block was not found.');

    // Test that only the 2 latest nodes are shown.
    $this->drupalLogin($this->web_user);
    $this->assertNoText($node1->label(), 'Node not found in block.');
    $this->assertText($node2->label(), 'Node found in block.');
    $this->assertText($node3->label(), 'Node found in block.');

    // Check to make sure nodes are in the right order.
    $this->assertTrue($this->xpath('//div[@id="block-node-recent"]/div/table/tbody/tr[position() = 1]/td/div/a[text() = "' . $node3->label() . '"]'), 'Nodes were ordered correctly in block.');

    // Set the number of recent nodes to show to 10.
    $this->drupalLogout();
    $this->drupalLogin($this->admin_user);
    $block = array(
      'node_recent_block_count' => 10,
    );
    $this->drupalPost('admin/structure/block/manage/node/recent/configure', $block, t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), 'Block saved.');

    // Post an additional node.
    $node4 = $this->drupalCreateNode($default_settings);
    // drupalCreateNode() does not automatically flush content caches unlike
    // posting a node from a node form.
    cache_invalidate_tags(array('content' => TRUE));

    // Test that all four nodes are shown.
    $this->drupalGet('');
    $this->assertText($node1->label(), 'Node found in block.');
    $this->assertText($node2->label(), 'Node found in block.');
    $this->assertText($node3->label(), 'Node found in block.');
    $this->assertText($node4->label(), 'Node found in block.');

    // Create the custom block.
    $custom_block = array();
    $custom_block['info'] = $this->randomName();
    $custom_block['title'] = $this->randomName();
    $custom_block['types[article]'] = TRUE;
    $custom_block['body[value]'] = $this->randomName(32);
    $custom_block['regions[' . variable_get('theme_default', 'stark') . ']'] = 'content';
    if ($admin_theme = config('system.site')->get('admin_theme')) {
      $custom_block['regions[' . $admin_theme . ']'] = 'content';
    }
    $this->drupalPost('admin/structure/block/add', $custom_block, t('Save block'));

    $bid = db_query("SELECT bid FROM {block_custom} WHERE info = :info", array(':info' => $custom_block['info']))->fetchField();
    $this->assertTrue($bid, 'Custom block with visibility rule was created.');

    // Verify visibility rules.
    $this->drupalGet('');
    $this->assertNoText($custom_block['title'], 'Block was displayed on the front page.');
    $this->drupalGet('node/add/article');
    $this->assertText($custom_block['title'], 'Block was displayed on the node/add/article page.');
    $this->drupalGet('node/' . $node1->nid);
    $this->assertText($custom_block['title'], 'Block was displayed on the node/N.');

    // Delete the created custom block & verify that it's been deleted.
    $this->drupalPost('admin/structure/block/manage/block/' . $bid . '/delete', array(), t('Delete'));
    $bid = db_query("SELECT 1 FROM {block_node_type} WHERE module = 'block' AND delta = :delta", array(':delta' => $bid))->fetchField();
    $this->assertFalse($bid, 'Custom block was deleted.');
  }
}
