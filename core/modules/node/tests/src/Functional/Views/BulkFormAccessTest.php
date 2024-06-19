<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional\Views;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests if entity access is respected on a node bulk operations form.
 *
 * @group node
 * @see \Drupal\node\Plugin\views\field\BulkForm
 * @see \Drupal\node\Tests\NodeTestBase
 * @see \Drupal\node\Tests\NodeAccessBaseTableTest
 * @see \Drupal\node\Tests\Views\BulkFormTest
 */
class BulkFormAccessTest extends NodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node_test_views', 'node_access_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_node_bulk_form'];

  /**
   * The node access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['node_test_views']): void {
    parent::setUp($import_test_views, $modules);

    // Create Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    $this->accessHandler = \Drupal::entityTypeManager()->getAccessControlHandler('node');

    node_access_test_add_field(NodeType::load('article'));

    // After enabling a node access module, the access table has to be rebuild.
    node_access_rebuild();

    // Enable the private node feature of the node_access_test module.
    \Drupal::state()->set('node_access_test.private', TRUE);
  }

  /**
   * Tests if nodes that may not be edited, can not be edited in bulk.
   */
  public function testNodeEditAccess(): void {
    // Create an account who will be the author of a private node.
    $author = $this->drupalCreateUser();
    // Create a private node (author may view, edit and delete, others may not).
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'private' => [
        ['value' => TRUE],
      ],
      'uid' => $author->id(),
    ]);
    // Create an account that may view the private node, but not edit it.
    $account = $this->drupalCreateUser(['node test view']);
    $this->drupalLogin($account);

    // Ensure the node is published.
    $this->assertTrue($node->isPublished(), 'Node is initially published.');

    // Ensure that the node can not be edited.
    $this->assertFalse($this->accessHandler->access($node, 'update', $account), 'The node may not be edited.');

    // Test editing the node using the bulk form.
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_unpublish_action',
    ];
    $this->drupalGet('test-node-bulk-form');
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->pageTextContains("No access to execute Unpublish content on the Content {$node->label()}.");

    // Re-load the node and check the status.
    $node = Node::load($node->id());
    $this->assertTrue($node->isPublished(), 'The node is still published.');

    // Create an account that may view the private node, but can update the
    // status.
    $account = $this->drupalCreateUser(['administer nodes', 'node test view']);
    $this->drupalLogin($account);

    // Ensure the node is published.
    $this->assertTrue($node->isPublished(), 'Node is initially published.');

    // Ensure that the private node can not be edited.
    $this->assertFalse($node->access('update', $account), 'The node may not be edited.');
    $this->assertTrue($node->status->access('edit', $account), 'The node status can be edited.');

    // Test editing the node using the bulk form.
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_unpublish_action',
    ];
    $this->drupalGet('test-node-bulk-form');
    $this->submitForm($edit, 'Apply to selected items');
    // Test that the action message isn't shown.
    $this->assertSession()->pageTextNotContains("Unpublish content was applied to 1 item.");
    // Re-load the node and check the status.
    $node = Node::load($node->id());
    $this->assertTrue($node->isPublished(), 'The node is still published.');

    // Try to delete the node and check that we are not redirected to the
    // conformation form but stay on the content view.
    $this->assertNotEmpty($this->cssSelect('#views-form-test-node-bulk-form-page-1'));
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_delete_action',
    ];
    $this->drupalGet('test-node-bulk-form');
    $this->submitForm($edit, 'Apply to selected items');
    // Test that the action message isn't shown.
    $this->assertSession()->pageTextContains("No access to execute Delete content on the Content {$node->label()}.");
    $this->assertNotEmpty($this->cssSelect('#views-form-test-node-bulk-form-page-1'));
  }

  /**
   * Tests if nodes that may not be deleted, can not be deleted in bulk.
   */
  public function testNodeDeleteAccess(): void {
    // Create an account who will be the author of a private node.
    $author = $this->drupalCreateUser();
    // Create a private node (author may view, edit and delete, others may not).
    $private_node = $this->drupalCreateNode([
      'type' => 'article',
      'private' => [
        ['value' => TRUE],
      ],
      'uid' => $author->id(),
    ]);
    // Create an account that may view the private node, but not delete it.
    $account = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'delete own article content',
      'node test view',
    ]);
    // Create a node that may be deleted too, to ensure the delete confirmation
    // page is shown later. In node_access_test.module, nodes may only be
    // deleted by the author.
    $own_node = $this->drupalCreateNode([
      'type' => 'article',
      'private' => [
        ['value' => TRUE],
      ],
      'uid' => $account->id(),
    ]);
    $this->drupalLogin($account);

    // Ensure that the private node can not be deleted.
    $this->assertFalse($this->accessHandler->access($private_node, 'delete', $account), 'The private node may not be deleted.');
    // Ensure that the public node may be deleted.
    $this->assertTrue($this->accessHandler->access($own_node, 'delete', $account), 'The own node may be deleted.');

    // Try to delete the node using the bulk form.
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'node_bulk_form[1]' => TRUE,
      'action' => 'node_delete_action',
    ];
    $this->drupalGet('test-node-bulk-form');
    $this->submitForm($edit, 'Apply to selected items');
    $this->submitForm([], 'Delete');
    // Ensure the private node still exists.
    $private_node = Node::load($private_node->id());
    $this->assertNotNull($private_node, 'The private node has not been deleted.');
    // Ensure the own node is deleted.
    $own_node = Node::load($own_node->id());
    $this->assertNull($own_node, 'The own node is deleted.');
  }

}
