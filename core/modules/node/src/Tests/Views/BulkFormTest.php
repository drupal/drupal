<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Views\BulkFormTest.
 */

namespace Drupal\node\Tests\Views;

use Drupal\node\Entity\Node;

/**
 * Tests a node bulk form.
 *
 * @group node
 * @see \Drupal\node\Plugin\views\field\BulkForm
 */
class BulkFormTest extends NodeTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_node_bulk_form');

  /**
   * Tests the node bulk form.
   */
  public function testBulkForm() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $this->drupalLogin($this->drupalCreateUser(array('administer nodes', 'access content overview', 'bypass node access')));
    $node = $this->drupalCreateNode(array(
      'promote' => FALSE,
    ));

    $this->drupalGet('test-node-bulk-form');
    $elements = $this->xpath('//select[@id="edit-action"]//option');
    $this->assertIdentical(count($elements), 8, 'All node operations are found.');

    // Unpublish a node using the bulk form.
    $this->assertTrue($node->isPublished(), 'Node is initially published');
    $edit = array(
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_unpublish_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    // Re-load the node and check the status.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertFalse($node->isPublished(), 'Node has been unpublished');

    // Publish action.
    $edit = array(
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_publish_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    // Re-load the node and check the status.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isPublished(), 'Node has been published');

    // Make sticky action.
    $this->assertFalse($node->isSticky(), 'Node is not sticky');
    $edit = array(
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_make_sticky_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    // Re-load the node and check the sticky flag.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isSticky(), 'Node has been made sticky');

    // Make unsticky action.
    $edit = array(
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_make_unsticky_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    // Re-load the node and check the sticky flag.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertFalse($node->isSticky(), 'Node is not sticky anymore');

    // Promote to front page.
    $this->assertFalse($node->isPromoted(), 'Node is not promoted to the front page');
    $edit = array(
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_promote_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    // Re-load the node and check the promoted flag.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertTrue($node->isPromoted(), 'Node has been promoted to the front page');

    // Demote from front page.
    $edit = array(
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_unpromote_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    // Re-load the node and check the promoted flag.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertFalse($node->isPromoted(), 'Node has been demoted');

    // Delete node.
    $edit = array(
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_delete_action',
    );
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    // Re-load the node and check if it has been deleted.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertNull($node, 'Node has been deleted');
  }

}
