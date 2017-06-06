<?php

namespace Drupal\node\Tests\Views;

use Drupal\node\Entity\NodeType;

/**
 * Tests the node_access filter handler.
 *
 * @group node
 * @see \Drupal\node\Plugin\views\filter\Access
 */
class FilterNodeAccessTest extends NodeTestBase {

  /**
   * An array of users.
   *
   * @var \Drupal\user\Entity\User[]
   */
  protected $users;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node_access_test'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_filter_node_access'];

  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    node_access_test_add_field(NodeType::load('article'));

    node_access_rebuild();
    \Drupal::state()->set('node_access_test.private', TRUE);

    $num_simple_users = 2;
    $this->users = [];

    for ($i = 0; $i < $num_simple_users; $i++) {
      $this->users[$i] = $this->drupalCreateUser(['access content', 'create article content']);
    }
    foreach ($this->users as $web_user) {
      $this->drupalLogin($web_user);
      foreach ([0 => 'Public', 1 => 'Private'] as $is_private => $type) {
        $settings = [
          'body' => [[
            'value' => $type . ' node',
            'format' => filter_default_format(),
          ]],
          'title' => t('@private_public Article created by @user', ['@private_public' => $type, '@user' => $web_user->getUsername()]),
          'type' => 'article',
          'uid' => $web_user->id(),
          'private' => (bool) $is_private,
        ];

        $node = $this->drupalCreateNode($settings);
        $this->assertEqual($is_private, (int) $node->private->value, 'The private status of the node was properly set in the node_access_test table.');
      }
    }
  }

  /**
   * Tests the node access filter.
   */
  public function testFilterNodeAccess() {
    $this->drupalLogin($this->users[0]);
    $this->drupalGet('test_filter_node_access');
    // Test that the private node of the current user is shown.
    $this->assertText('Private Article created by ' . $this->users[0]->getUsername());
    // Test that the private node of the other use isn't shown.
    $this->assertNoText('Private Article created by ' . $this->users[1]->getUsername());
    // Test that both public nodes are shown.
    $this->assertText('Public Article created by ' . $this->users[0]->getUsername());
    $this->assertText('Public Article created by ' . $this->users[1]->getUsername());

    // Switch users and test the other private node is shown.
    $this->drupalLogin($this->users[1]);
    $this->drupalGet('test_filter_node_access');
    // Test that the private node of the current user is shown.
    $this->assertText('Private Article created by ' . $this->users[1]->getUsername());
    // Test that the private node of the other use isn't shown.
    $this->assertNoText('Private Article created by ' . $this->users[0]->getUsername());

    // Test that a user with administer nodes permission can't see all nodes.
    $administer_nodes_user = $this->drupalCreateUser(['access content', 'administer nodes']);
    $this->drupalLogin($administer_nodes_user);
    $this->drupalGet('test_filter_node_access');
    $this->assertNoText('Private Article created by ' . $this->users[0]->getUsername());
    $this->assertNoText('Private Article created by ' . $this->users[1]->getUsername());
    $this->assertText('Public Article created by ' . $this->users[0]->getUsername());
    $this->assertText('Public Article created by ' . $this->users[1]->getUsername());

    // Test that a user with bypass node access can see all nodes.
    $bypass_access_user = $this->drupalCreateUser(['access content', 'bypass node access']);
    $this->drupalLogin($bypass_access_user);
    $this->drupalGet('test_filter_node_access');
    $this->assertText('Private Article created by ' . $this->users[0]->getUsername());
    $this->assertText('Private Article created by ' . $this->users[1]->getUsername());
    $this->assertText('Public Article created by ' . $this->users[0]->getUsername());
    $this->assertText('Public Article created by ' . $this->users[1]->getUsername());
  }

}
