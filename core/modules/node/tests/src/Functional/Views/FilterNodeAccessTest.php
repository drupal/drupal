<?php

namespace Drupal\Tests\node\Functional\Views;

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
  protected static $modules = ['node_access_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_filter_node_access'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['node_test_views']): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    node_access_test_add_field(NodeType::load('article'));

    node_access_rebuild();
    \Drupal::state()->set('node_access_test.private', TRUE);

    $num_simple_users = 2;
    $this->users = [];

    for ($i = 0; $i < $num_simple_users; $i++) {
      $this->users[$i] = $this->drupalCreateUser([
        'access content',
        'create article content',
      ]);
    }
    foreach ($this->users as $web_user) {
      $this->drupalLogin($web_user);
      foreach ([0 => 'Public', 1 => 'Private'] as $is_private => $type) {
        $settings = [
          'body' => [
            [
              'value' => $type . ' node',
              'format' => filter_default_format(),
            ],
          ],
          'title' => "$type Article created by " . $web_user->getAccountName(),
          'type' => 'article',
          'uid' => $web_user->id(),
          'private' => (bool) $is_private,
        ];

        $node = $this->drupalCreateNode($settings);
        $this->assertEquals($is_private, (int) $node->private->value, 'The private status of the node was properly set in the node_access_test table.');
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
    $this->assertSession()->pageTextContains('Private Article created by ' . $this->users[0]->getAccountName());
    // Test that the private node of the other use isn't shown.
    $this->assertSession()->pageTextNotContains('Private Article created by ' . $this->users[1]->getAccountName());
    // Test that both public nodes are shown.
    $this->assertSession()->pageTextContains('Public Article created by ' . $this->users[0]->getAccountName());
    $this->assertSession()->pageTextContains('Public Article created by ' . $this->users[1]->getAccountName());

    // Switch users and test the other private node is shown.
    $this->drupalLogin($this->users[1]);
    $this->drupalGet('test_filter_node_access');
    // Test that the private node of the current user is shown.
    $this->assertSession()->pageTextContains('Private Article created by ' . $this->users[1]->getAccountName());
    // Test that the private node of the other use isn't shown.
    $this->assertSession()->pageTextNotContains('Private Article created by ' . $this->users[0]->getAccountName());

    // Test that a user with administer nodes permission can't see all nodes.
    $administer_nodes_user = $this->drupalCreateUser([
      'access content',
      'administer nodes',
    ]);
    $this->drupalLogin($administer_nodes_user);
    $this->drupalGet('test_filter_node_access');
    $this->assertSession()->pageTextNotContains('Private Article created by ' . $this->users[0]->getAccountName());
    $this->assertSession()->pageTextNotContains('Private Article created by ' . $this->users[1]->getAccountName());
    $this->assertSession()->pageTextContains('Public Article created by ' . $this->users[0]->getAccountName());
    $this->assertSession()->pageTextContains('Public Article created by ' . $this->users[1]->getAccountName());

    // Test that a user with bypass node access can see all nodes.
    $bypass_access_user = $this->drupalCreateUser([
      'access content',
      'bypass node access',
    ]);
    $this->drupalLogin($bypass_access_user);
    $this->drupalGet('test_filter_node_access');
    $this->assertSession()->pageTextContains('Private Article created by ' . $this->users[0]->getAccountName());
    $this->assertSession()->pageTextContains('Private Article created by ' . $this->users[1]->getAccountName());
    $this->assertSession()->pageTextContains('Public Article created by ' . $this->users[0]->getAccountName());
    $this->assertSession()->pageTextContains('Public Article created by ' . $this->users[1]->getAccountName());
  }

}
