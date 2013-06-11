<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Views\BulkFormTest.
 */

namespace Drupal\node\Tests\Views;

/**
 * Tests the views bulk form test.
 *
 * @see \Drupal\node\Plugin\views\field\BulkForm
 */
class BulkFormTest extends NodeTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_node_bulk_form');

  public static function getInfo() {
    return array(
      'name' => 'Node: Bulk form',
      'description' => 'Tests a node bulk form.',
      'group' => 'Views Modules',
    );
  }

  /**
   * Tests the node bulk form.
   */
  public function testBulkForm() {
    $this->drupalLogin($this->drupalCreateUser(array('administer nodes')));
    $node = $this->drupalCreateNode();

    $this->drupalGet('test-node-bulk-form');
    $elements = $this->xpath('//select[@id="edit-action"]//option');
    $this->assertIdentical(count($elements), 8, 'All node operations are found.');

    // Block a node using the bulk form.
    $this->assertTrue($node->status);
    $edit = array(
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_unpublish_action',
    );
    $this->drupalPost(NULL, $edit, t('Apply'));
    // Re-load the node and check their status.
    $node = entity_load('node', $node->id());
    $this->assertFalse($node->status);
  }

}
