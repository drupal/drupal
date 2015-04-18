<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Views\BulkFormAccessTest.
 */

namespace Drupal\node\Tests\Views;

use Drupal\Component\Utility\SafeMarkup;
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
  public static $modules = array('node_test_views', 'node_access_test');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_node_bulk_form');

  /**
   * The node access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Article node type.
    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));

    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('node');

    node_access_test_add_field(NodeType::load('article'));

    // After enabling a node access module, the access table has to be rebuild.
    node_access_rebuild();

    // Enable the private node feature of the node_access_test module.
    \Drupal::state()->set('node_access_test.private', TRUE);
  }

  /**
   * Tests if nodes that may not be edited, can not be edited in bulk.
   */
  public function testNodeEditAccess() {
    // Create an account who will be the author of a private node.
    $author = $this->drupalCreateUser();
    // Create a private node (author may view, edit and delete, others may not).
    $node = $this->drupalCreateNode(array(
      'type' => 'article',
      'private' => array(array(
        'value' => TRUE,
      )),
      'uid' => $author->id(),
    ));
    // Create an account that may view the private node, but not edit it.
    $account = $this->drupalCreateUser(array('node test view'));
    $this->drupalLogin($account);

    // Ensure the node is published.
    $this->assertTrue($node->isPublished(), 'Node is initially published.');

    // Ensure that the node can not be edited.
    $this->assertEqual(FALSE, $this->accessHandler->access($node, 'update', $node->prepareLangcode(), $account), 'The node may not be edited.');

    // Test editing the node using the bulk form.
    $edit = array(
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_unpublish_action',
    );
    $this->drupalPostForm('test-node-bulk-form', $edit, t('Apply'));
    $this->assertRaw(SafeMarkup::format('No access to execute %action on the @entity_type_label %entity_label.', [
      '%action' => 'Unpublish content',
      '@entity_type_label' => 'Content',
      '%entity_label' => $node->label(),
    ]));

    // Re-load the node and check the status.
    $node = Node::load($node->id());
    $this->assertTrue($node->isPublished(), 'The node is still published.');

    // Create an account that may view the private node, but can update the
    // status.
    $account = $this->drupalCreateUser(array('administer nodes', 'node test view'));
    $this->drupalLogin($account);

    // Ensure the node is published.
    $this->assertTrue($node->isPublished(), 'Node is initially published.');

    // Ensure that the private node can not be edited.
    $this->assertEqual(FALSE, $node->access('update', $account), 'The node may not be edited.');
    $this->assertEqual(TRUE, $node->status->access('edit', $account), 'The node status can be edited.');

    // Test editing the node using the bulk form.
    $edit = array(
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_unpublish_action',
    );
    $this->drupalPostForm('test-node-bulk-form', $edit, t('Apply'));
    // Re-load the node and check the status.
    $node = Node::load($node->id());
    $this->assertTrue($node->isPublished(), 'The node is still published.');
  }

  /**
   * Tests if nodes that may not be deleted, can not be deleted in bulk.
   */
  public function testNodeDeleteAccess() {
    // Create an account who will be the author of a private node.
    $author = $this->drupalCreateUser();
    // Create a private node (author may view, edit and delete, others may not).
    $private_node = $this->drupalCreateNode(array(
      'type' => 'article',
      'private' => array(array(
        'value' => TRUE,
      )),
      'uid' => $author->id(),
    ));
    // Create an account that may view the private node, but not delete it.
    $account = $this->drupalCreateUser(array('access content', 'administer nodes', 'delete own article content', 'node test view'));
    // Create a node that may be deleted too, to ensure the delete confirmation
    // page is shown later. In node_access_test.module, nodes may only be
    // deleted by the author.
    $own_node = $this->drupalCreateNode(array(
      'type' => 'article',
      'private' => array(array(
        'value' => TRUE,
      )),
      'uid' => $account->id(),
    ));
    $this->drupalLogin($account);

    // Ensure that the private node can not be deleted.
    $this->assertEqual(FALSE, $this->accessHandler->access($private_node, 'delete', $private_node->prepareLangcode(), $account), 'The private node may not be deleted.');
    // Ensure that the public node may be deleted.
    $this->assertEqual(TRUE, $this->accessHandler->access($own_node, 'delete', $own_node->prepareLangcode(), $account), 'The own node may be deleted.');

    // Try to delete the node using the bulk form.
    $edit = array(
      'node_bulk_form[0]' => TRUE,
      'node_bulk_form[1]' => TRUE,
      'action' => 'node_delete_action',
    );
    $this->drupalPostForm('test-node-bulk-form', $edit, t('Apply'));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    // Ensure the private node still exists.
    $private_node = Node::load($private_node->id());
    $this->assertNotNull($private_node, 'The private node has not been deleted.');
    // Ensure the own node is deleted.
    $own_node = Node::load($own_node->id());
    $this->assertNull($own_node, 'The own node is deleted.');
  }
}
