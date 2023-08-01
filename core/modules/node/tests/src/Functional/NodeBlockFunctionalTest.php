<?php

namespace Drupal\Tests\node\Functional;

use Drupal\block\Entity\Block;
use Drupal\Core\Database\Database;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Url;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\user\RoleInterface;

/**
 * Tests node block functionality.
 *
 * @group node
 */
class NodeBlockFunctionalTest extends NodeTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected static $modules = ['block', 'views', 'node_block_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create users and test node.
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer nodes',
      'bypass node access',
      'administer blocks',
      'access content overview',
    ]);
    $this->webUser = $this->drupalCreateUser([
      'access content',
      'create article content',
    ]);
  }

  /**
   * Tests the recent comments block.
   */
  public function testRecentNodeBlock() {
    $this->drupalLogin($this->adminUser);

    // Disallow anonymous users to view content.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, [
      'access content' => FALSE,
    ]);

    // Enable the recent content block with two items.
    $block = $this->drupalPlaceBlock('views_block:content_recent-block_1', ['id' => 'test_block', 'items_per_page' => 2]);

    // Test that block is not visible without nodes.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains('No content available.');

    // Add some test nodes.
    $default_settings = ['uid' => $this->webUser->id(), 'type' => 'article'];
    $node1 = $this->drupalCreateNode($default_settings);
    $node2 = $this->drupalCreateNode($default_settings);
    $node3 = $this->drupalCreateNode($default_settings);

    // Create a second revision of node1.
    $node1_revision_1 = $node1;
    $node1->setNewRevision(TRUE);
    $node1->setTitle('Node revision 2 title');
    $node1->save();

    $connection = Database::getConnection();
    // Change the changed time for node so that we can test ordering.
    $connection->update('node_field_data')
      ->fields([
        'changed' => $node1->getChangedTime() + 100,
      ])
      ->condition('nid', $node2->id())
      ->execute();
    $connection->update('node_field_data')
      ->fields([
        'changed' => $node1->getChangedTime() + 200,
      ])
      ->condition('nid', $node3->id())
      ->execute();

    // Test that a user without the 'access content' permission cannot
    // see the block.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertSession()->pageTextNotContains($block->label());

    // Test that only the 2 latest nodes are shown.
    $this->drupalLogin($this->webUser);
    $this->assertSession()->pageTextNotContains($node1->label());
    $this->assertSession()->pageTextContains($node2->label());
    $this->assertSession()->pageTextContains($node3->label());

    // Check to make sure nodes are in the right order.
    $this->assertSession()->elementExists('xpath', '//div[@id="block-test-block"]//div[@class="item-list"]/ul/li[1]/div/span/a[text() = "' . $node3->label() . '"]');

    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);

    // Set the number of recent nodes to show to 10.
    $block->getPlugin()->setConfigurationValue('items_per_page', 10);
    $block->save();

    // Post an additional node.
    $node4 = $this->drupalCreateNode($default_settings);

    // Test that all four nodes are shown.
    $this->drupalGet('');
    $this->assertSession()->pageTextContains($node1->label());
    $this->assertSession()->pageTextContains($node2->label());
    $this->assertSession()->pageTextContains($node3->label());
    $this->assertSession()->pageTextContains($node4->label());

    $this->assertCacheContexts(['languages:language_content', 'languages:language_interface', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'url.site', 'user']);

    // Enable the "Powered by Drupal" block only on article nodes.
    $theme = \Drupal::service('theme_handler')->getDefault();
    $this->drupalGet("admin/structure/block/add/system_powered_by_block/{$theme}");
    $this->assertSession()->pageTextContains('Content type');
    $edit = [
      'id' => $this->randomMachineName(),
      'region' => 'sidebar_first',
      'visibility[entity_bundle:node][bundles][article]' => 'article',
    ];
    $this->submitForm($edit, 'Save block');

    $block = Block::load($edit['id']);
    $visibility = $block->getVisibility();
    $this->assertTrue(isset($visibility['entity_bundle:node']['bundles']['article']), 'Visibility settings were saved to configuration');

    // Create a page node.
    $node5 = $this->drupalCreateNode(['uid' => $this->adminUser->id(), 'type' => 'page']);

    $this->drupalLogout();
    $this->drupalLogin($this->webUser);

    // Verify visibility rules.
    $this->drupalGet('');
    $label = $block->label();
    // Check that block is not displayed on the front page.
    $this->assertSession()->pageTextNotContains($label);
    $this->assertCacheContexts(['languages:language_content', 'languages:language_interface', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'url.site', 'user', 'route']);

    // Ensure that a page that does not have a node context can still be cached,
    // the front page is the user page which is already cached from the login
    // request above.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'HIT');

    $this->drupalGet('node/add/article');
    // Check that block is displayed on the add article page.
    $this->assertSession()->pageTextContains($label);
    $this->assertCacheContexts(['languages:language_content', 'languages:language_interface', 'session', 'theme', 'url.path', 'url.query_args', 'user', 'route']);

    // The node/add/article page is an admin path and currently uncacheable.
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->drupalGet('node/' . $node1->id());
    // Check that block is displayed on the node page when node is of type
    // 'article'.
    $this->assertSession()->pageTextContains($label);
    $this->assertCacheContexts(['languages:language_content', 'languages:language_interface', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'url.site', 'user', 'route', 'timezone']);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'MISS');
    $this->drupalGet('node/' . $node1->id());
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'HIT');

    $this->drupalGet('node/' . $node5->id());
    // Check that block is not displayed on the node page when node is of type
    // 'page'.
    $this->assertSession()->pageTextNotContains($label);
    $this->assertCacheContexts(['languages:language_content', 'languages:language_interface', 'theme', 'url.query_args:' . MainContentViewSubscriber::WRAPPER_FORMAT, 'url.site', 'user', 'route', 'timezone']);
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'MISS');
    $this->drupalGet('node/' . $node5->id());
    $this->assertSession()->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'HIT');

    // Place a block to determine which revision is provided as context
    // to blocks.
    $this->drupalPlaceBlock('node_block_test_context', [
      'context_mapping' => ['node' => '@node.node_route_context:node'],
    ]);

    $this->drupalLogin($this->adminUser);

    $this->drupalGet('node/' . $node1->id());
    $this->assertSession()->pageTextContains($label);
    $this->assertSession()->pageTextContains('Displaying node #' . $node1->id() . ', revision #' . $node1->getRevisionId() . ': Node revision 2 title');

    // Assert that the preview page displays the block as well.
    $this->drupalGet('node/' . $node1->id() . '/edit');
    $this->submitForm([], 'Preview');
    $this->assertSession()->pageTextContains($label);
    // The previewed node object has no revision ID.
    $this->assertSession()->pageTextContains('Displaying node #' . $node1->id() . ', revision #: Node revision 2 title');

    // Assert that the revision page for both revisions displays the block.
    $this->drupalGet(Url::fromRoute('entity.node.revision', ['node' => $node1->id(), 'node_revision' => $node1_revision_1->getRevisionId()]));
    $this->assertSession()->pageTextContains($label);
    $this->assertSession()->pageTextContains('Displaying node #' . $node1->id() . ', revision #' . $node1_revision_1->getRevisionId() . ': ' . $node1_revision_1->label());

    $this->drupalGet(Url::fromRoute('entity.node.revision', ['node' => $node1->id(), 'node_revision' => $node1->getRevisionId()]));
    $this->assertSession()->pageTextContains($label);
    $this->assertSession()->pageTextContains('Displaying node #' . $node1->id() . ', revision #' . $node1->getRevisionId() . ': Node revision 2 title');

    $this->drupalGet('admin/structure/block');
    // Check that block is displayed on the admin/structure/block page.
    $this->assertSession()->pageTextContains($label);
    $this->assertSession()->linkByHrefExists($block->toUrl()->toString());
  }

}
