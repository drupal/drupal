<?php

/**
 * @file
 * Contains \Drupal\node\Tests\NodeBlockFunctionalTest.
 */

namespace Drupal\node\Tests;

use Drupal\block\Entity\Block;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\system\Tests\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\user\RoleInterface;

/**
 * Tests node block functionality.
 *
 * @group node
 */
class NodeBlockFunctionalTest extends NodeTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * An administrative user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * An unprivileged user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'views');

  protected function setUp() {
    parent::setUp();

    // Create users and test node.
    $this->adminUser = $this->drupalCreateUser(array('administer content types', 'administer nodes', 'administer blocks', 'access content overview'));
    $this->webUser = $this->drupalCreateUser(array('access content', 'create article content'));
  }

  /**
   * Tests the recent comments block.
   */
  public function testRecentNodeBlock() {
    $this->drupalLogin($this->adminUser);

    // Disallow anonymous users to view content.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, array(
      'access content' => FALSE,
    ));

    // Enable the recent content block with two items.
    $block = $this->drupalPlaceBlock('views_block:content_recent-block_1', array('id' => 'test_block', 'items_per_page' => 2));

    // Test that block is not visible without nodes.
    $this->drupalGet('');
    $this->assertText(t('No content available.'), 'Block with "No content available." found.');

    // Add some test nodes.
    $default_settings = array('uid' => $this->webUser->id(), 'type' => 'article');
    $node1 = $this->drupalCreateNode($default_settings);
    $node2 = $this->drupalCreateNode($default_settings);
    $node3 = $this->drupalCreateNode($default_settings);

    // Change the changed time for node so that we can test ordering.
    db_update('node_field_data')
      ->fields(array(
        'changed' => $node1->getChangedTime() + 100,
      ))
      ->condition('nid', $node2->id())
      ->execute();
    db_update('node_field_data')
      ->fields(array(
        'changed' => $node1->getChangedTime() + 200,
      ))
      ->condition('nid', $node3->id())
      ->execute();

    // Test that a user without the 'access content' permission cannot
    // see the block.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoText($block->label(), 'Block was not found.');

    // Test that only the 2 latest nodes are shown.
    $this->drupalLogin($this->webUser);
    $this->assertNoText($node1->label(), 'Node not found in block.');
    $this->assertText($node2->label(), 'Node found in block.');
    $this->assertText($node3->label(), 'Node found in block.');

    // Check to make sure nodes are in the right order.
    $this->assertTrue($this->xpath('//div[@id="block-test-block"]//div[@class="item-list"]/ul/li[1]/div/span/a[text() = "' . $node3->label() . '"]'), 'Nodes were ordered correctly in block.');

    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);

    // Set the number of recent nodes to show to 10.
    $block->getPlugin()->setConfigurationValue('items_per_page', 10);
    $block->save();

    // Post an additional node.
    $node4 = $this->drupalCreateNode($default_settings);

    // Test that all four nodes are shown.
    $this->drupalGet('');
    $this->assertText($node1->label(), 'Node found in block.');
    $this->assertText($node2->label(), 'Node found in block.');
    $this->assertText($node3->label(), 'Node found in block.');
    $this->assertText($node4->label(), 'Node found in block.');

    $this->assertCacheContexts(['languages:language_content', 'languages:language_interface', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'user']);

    // Enable the "Powered by Drupal" block only on article nodes.
    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'region' => 'sidebar_first',
      'visibility[node_type][bundles][article]' => 'article',
    ];
    $theme =  \Drupal::service('theme_handler')->getDefault();
    $this->drupalPostForm("admin/structure/block/add/system_powered_by_block/$theme", $edit, t('Save block'));

    $block = Block::load($edit['id']);
    $visibility = $block->getVisibility();
    $this->assertTrue(isset($visibility['node_type']['bundles']['article']), 'Visibility settings were saved to configuration');

    // Create a page node.
    $node5 = $this->drupalCreateNode(array('uid' => $this->adminUser->id(), 'type' => 'page'));

    $this->drupalLogout();
    $this->drupalLogin($this->webUser);

    // Verify visibility rules.
    $this->drupalGet('');
    $label = $block->label();
    $this->assertNoText($label, 'Block was not displayed on the front page.');
    $this->assertCacheContexts(['languages:language_content', 'languages:language_interface', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'user', 'route']);
    $this->drupalGet('node/add/article');
    $this->assertText($label, 'Block was displayed on the node/add/article page.');
    $this->assertCacheContexts(['languages:language_content', 'languages:language_interface', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'user', 'route']);
    $this->drupalGet('node/' . $node1->id());
    $this->assertText($label, 'Block was displayed on the node/N when node is of type article.');
    $this->assertCacheContexts(['languages:language_content', 'languages:language_interface', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'user', 'route', 'timezone']);
    $this->drupalGet('node/' . $node5->id());
    $this->assertNoText($label, 'Block was not displayed on nodes of type page.');
    $this->assertCacheContexts(['languages:language_content', 'languages:language_interface', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'user', 'route', 'timezone']);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/block');
    $this->assertText($label, 'Block was displayed on the admin/structure/block page.');
    $this->assertLinkByHref($block->url());
  }

}
