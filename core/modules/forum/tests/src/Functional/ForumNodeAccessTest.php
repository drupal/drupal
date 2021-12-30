<?php

namespace Drupal\Tests\forum\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests forum block view for private node access.
 *
 * @group forum
 */
class ForumNodeAccessTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'comment',
    'forum',
    'taxonomy',
    'tracker',
    'node_access_test',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    node_access_rebuild();
    node_access_test_add_field(NodeType::load('forum'));
    \Drupal::state()->set('node_access_test.private', TRUE);
  }

  /**
   * Creates some users and creates a public node and a private node.
   *
   * Adds both active forum topics and new forum topics blocks to the sidebar.
   * Tests to ensure private node/public node access is respected on blocks.
   */
  public function testForumNodeAccess() {
    // Create some users.
    $access_user = $this->drupalCreateUser(['node test view']);
    $no_access_user = $this->drupalCreateUser();
    $admin_user = $this->drupalCreateUser([
      'access administration pages',
      'administer modules',
      'administer blocks',
      'create forum content',
    ]);

    $this->drupalLogin($admin_user);

    // Create a private node.
    $private_node_title = $this->randomMachineName(20);
    $edit = [
      'title[0][value]' => $private_node_title,
      'body[0][value]' => $this->randomMachineName(200),
      'private[0][value]' => TRUE,
    ];
    $this->drupalGet('node/add/forum', ['query' => ['forum_id' => 1]]);
    $this->submitForm($edit, 'Save');
    $private_node = $this->drupalGetNodeByTitle($private_node_title);
    $this->assertNotEmpty($private_node, 'New private forum node found in database.');

    // Create a public node.
    $public_node_title = $this->randomMachineName(20);
    $edit = [
      'title[0][value]' => $public_node_title,
      'body[0][value]' => $this->randomMachineName(200),
    ];
    $this->drupalGet('node/add/forum', ['query' => ['forum_id' => 1]]);
    $this->submitForm($edit, 'Save');
    $public_node = $this->drupalGetNodeByTitle($public_node_title);
    $this->assertNotEmpty($public_node, 'New public forum node found in database.');

    // Enable the new and active forum blocks.
    $this->drupalPlaceBlock('forum_active_block');
    $this->drupalPlaceBlock('forum_new_block');

    // Test for $access_user.
    $this->drupalLogin($access_user);
    $this->drupalGet('');

    // Ensure private node and public node are found.
    $this->assertSession()->pageTextContains($private_node->getTitle());
    $this->assertSession()->pageTextContains($public_node->getTitle());

    // Test for $no_access_user.
    $this->drupalLogin($no_access_user);
    $this->drupalGet('');

    // Ensure private node is not found but public is found.
    $this->assertSession()->pageTextNotContains($private_node->getTitle());
    $this->assertSession()->pageTextContains($public_node->getTitle());
  }

}
