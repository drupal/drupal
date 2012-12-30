<?php

/**
 * @file
 * Contains \Drupal\action\Tests\BulkFormTest.
 */

namespace Drupal\action\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the views bulk form test.
 *
 * @see \Drupal\action\Plugin\views\field\BulkForm
 */
class BulkFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('action_bulk_test');

  public static function getInfo() {
    return array(
      'name' => 'Bulk form',
      'description' => 'Tests the views bulk form test.',
      'group' => 'Action',
    );
  }

  /**
   * Tests the bulk form.
   */
  public function testBulkForm() {
    $nodes = array();
    for ($i = 0; $i < 10; $i++) {
      $nodes[] = $this->drupalCreateNode(array('sticky' => FALSE));
    }

    $this->drupalGet('test_bulk_form');

    $this->assertFieldById('edit-action', NULL, 'The action select field appears.');

    // Make sure a checkbox appears on all rows.
    $edit = array();
    for ($i = 0; $i < 10; $i++) {
      $this->assertFieldById('edit-bulk-form-' . $i, NULL, format_string('The checkbox on row @row appears.', array('@row' => $i)));
      $edit["bulk_form[$i]"] = TRUE;
    }

    // Set all nodes to sticky and check that.
    $edit += array('action' => 'node_make_sticky_action');
    $this->drupalPost(NULL, $edit, t('Apply'));

    foreach ($nodes as $node) {
      $changed_node = node_load($node->id());
      $this->assertTrue($changed_node->sticky, format_string('Node @nid got marked as sticky.', array('@nid' => $node->id())));
    }

    $this->assertText('Make content sticky was applied to 10 items.');

    // Unpublish just one node.
    $node = node_load($nodes[0]->id());
    $this->assertTrue($node->status, 'The node is published.');

    $edit = array('bulk_form[0]' => TRUE, 'action' => 'node_unpublish_action');
    $this->drupalPost(NULL, $edit, t('Apply'));

    $this->assertText('Unpublish content was applied to 1 item.');

    // Load the node again.
    $node = node_load($node->id(), TRUE);
    $this->assertFalse($node->status, 'A single node has been unpublished.');

    // The second node should still be published.
    $node = node_load($nodes[1]->id(), TRUE);
    $this->assertTrue($node->status, 'An unchecked node is still published.');
  }

}
