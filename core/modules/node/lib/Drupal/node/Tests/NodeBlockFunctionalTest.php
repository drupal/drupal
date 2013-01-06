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
   * An administrative user for testing.
   *
   * @var \Drupal\user\Plugin\Core\Entity\User
   */
  protected $adminUser;

  /**
   * An unprivileged user for testing.
   *
   * @var \Drupal\user\Plugin\Core\Entity\User
   */
  protected $webUser;

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
    $this->adminUser = $this->drupalCreateUser(array('administer content types', 'administer nodes', 'administer blocks'));
    $this->webUser = $this->drupalCreateUser(array('access content', 'create article content'));
  }

  /**
   * Tests the recent comments block.
   */
  public function testRecentNodeBlock() {
    $this->drupalLogin($this->adminUser);

    // Disallow anonymous users to view content.
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array(
      'access content' => FALSE,
    ));

    // Enable the recent content block.
    $block_id = 'node_recent_block';
    $default_theme = variable_get('theme_default', 'stark');
    $block = array(
      'title' => $this->randomName(8),
      'machine_name' => $this->randomName(8),
      'region' => 'sidebar_first',
    );
    $this->drupalPost('admin/structure/block/manage/' . $block_id . '/' . $default_theme, $block, t('Save block'));
    $this->assertText(t('The block configuration has been saved.'), 'Node enabled.');

    // Set the number of recent posts to 2.
    $block['config_id'] = 'plugin.core.block.' . $default_theme . '.' . $block['machine_name'];
    $config = config($block['config_id']);
    $config->set('block_count', 2);
    $config->save();

    // Test that block is not visible without nodes.
    $this->drupalGet('');
    $this->assertText(t('No content available.'), 'Block with "No content available." found.');

    // Add some test nodes.
    $default_settings = array('uid' => $this->webUser->uid, 'type' => 'article');
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
    $this->drupalLogin($this->webUser);
    $this->assertNoText($node1->label(), 'Node not found in block.');
    $this->assertText($node2->label(), 'Node found in block.');
    $this->assertText($node3->label(), 'Node found in block.');

    // Check to make sure nodes are in the right order.
    $this->assertTrue($this->xpath('//div[@id="block-' . strtolower($block['machine_name']) . '"]/div/table/tbody/tr[position() = 1]/td/div/a[text() = "' . $node3->label() . '"]'), 'Nodes were ordered correctly in block.');

    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);

    // Set the number of recent nodes to show to 10.
    $config = config($block['config_id']);
    $config->set('block_count', 10);
    $config->save();

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

    // Enable the "Powered by Drupal" block and test the visibility by node
    // type functionality.
    $block_name = 'system_powered_by_block';
    $block = array(
      'machine_name' => $this->randomName(8),
      'region' => 'sidebar_first',
      'title' => $this->randomName(8),
      'visibility[node_type][types][article]' => TRUE,
    );
    // Set the block to be shown only on node/xx if node is an article.
    $this->drupalPost('admin/structure/block/manage/' . $block_name . '/' . $default_theme, $block, t('Save block'));
    $this->assertText('The block configuration has been saved.', 'Block was saved');

    // Configure the new forum topics block to only show 2 topics.
    $block['config_id'] = 'plugin.core.block.' . $default_theme . '.' . $block['machine_name'];
    $config = config($block['config_id']);
    $node_type_visibility = $config->get('visibility.node_type.types.article');
    $this->assertEqual($node_type_visibility, 'article', 'Visibility settings were saved to configuration');

    // Create a page node.
    $node5 = $this->drupalCreateNode(array('uid' => $this->adminUser->uid, 'type' => 'page'));

    // Verify visibility rules.
    $this->drupalGet('');
    $this->assertNoText($block['title'], 'Block was not displayed on the front page.');
    $this->drupalGet('node/add/article');
    $this->assertText($block['title'], 'Block was displayed on the node/add/article page.');
    $this->drupalGet('node/' . $node1->nid);
    $this->assertText($block['title'], 'Block was displayed on the node/N when node is of type article.');
    $this->drupalGet('node/' . $node5->nid);
    $this->assertNoText($block['title'], 'Block was not displayed on nodes of type page.');
  }
}
