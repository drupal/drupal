<?php

/**
 * @file
 * Contains \Drupal\action\Tests\BulkFormTest.
 */

namespace Drupal\action\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\views\Views;

/**
 * Tests the views bulk form test.
 *
 * @group action
 * @see \Drupal\action\Plugin\views\field\BulkForm
 */
class BulkFormTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('node', 'action_bulk_test');

  /**
   * Tests the bulk form.
   */
  public function testBulkForm() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');

    // First, test an empty bulk form with the default style plugin to make sure
    // the empty region is rendered correctly.
    $this->drupalGet('test_bulk_form_empty');
    $this->assertText(t('This view is empty.'), 'Empty text found on empty bulk form.');

    $nodes = array();
    for ($i = 0; $i < 10; $i++) {
      // Ensure nodes are sorted in the same order they are inserted in the
      // array.
      $timestamp = REQUEST_TIME - $i;
      $nodes[] = $this->drupalCreateNode(array(
        'sticky' => FALSE,
        'created' => $timestamp,
        'changed' => $timestamp,
      ));
    }

    $this->drupalGet('test_bulk_form');

    // Test that the views edit header appears first.
    $first_form_element = $this->xpath('//form/div[1][@id = :id]', array(':id' => 'edit-header'));
    $this->assertTrue($first_form_element, 'The views form edit header appears first.');

    $this->assertFieldById('edit-action', NULL, 'The action select field appears.');

    // Make sure a checkbox appears on all rows.
    $edit = array();
    for ($i = 0; $i < 10; $i++) {
      $this->assertFieldById('edit-node-bulk-form-' . $i, NULL, format_string('The checkbox on row @row appears.', array('@row' => $i)));
      $edit["node_bulk_form[$i]"] = TRUE;
    }

    // Log in as a user with 'administer nodes' permission to have access to the
    // bulk operation.
    $this->drupalCreateContentType(['type' => 'page']);
    $admin_user = $this->drupalCreateUser(['administer nodes', 'edit any page content', 'delete any page content']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('test_bulk_form');

    // Set all nodes to sticky and check that.
    $edit += array('action' => 'node_make_sticky_action');
    $this->drupalPostForm(NULL, $edit, t('Apply'));

    foreach ($nodes as $node) {
      $changed_node = $node_storage->load($node->id());
      $this->assertTrue($changed_node->isSticky(), format_string('Node @nid got marked as sticky.', array('@nid' => $node->id())));
    }

    $this->assertText('Make content sticky was applied to 10 items.');

    // Unpublish just one node.
    $node = $node_storage->load($nodes[0]->id());
    $this->assertTrue($node->isPublished(), 'The node is published.');

    $edit = array('node_bulk_form[0]' => TRUE, 'action' => 'node_unpublish_action');
    $this->drupalPostForm(NULL, $edit, t('Apply'));

    $this->assertText('Unpublish content was applied to 1 item.');

    // Load the node again.
    $node_storage->resetCache(array($node->id()));
    $node = $node_storage->load($node->id());
    $this->assertFalse($node->isPublished(), 'A single node has been unpublished.');

    // The second node should still be published.
    $node_storage->resetCache(array($nodes[1]->id()));
    $node = $node_storage->load($nodes[1]->id());
    $this->assertTrue($node->isPublished(), 'An unchecked node is still published.');

    // Set up to include just the sticky actions.
    $view = Views::getView('test_bulk_form');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['fields']['node_bulk_form']['include_exclude'] = 'include';
    $display['display_options']['fields']['node_bulk_form']['selected_actions']['node_make_sticky_action'] = 'node_make_sticky_action';
    $display['display_options']['fields']['node_bulk_form']['selected_actions']['node_make_unsticky_action'] = 'node_make_unsticky_action';
    $view->save();

    $this->drupalGet('test_bulk_form');
    $options = $this->xpath('//select[@id=:id]/option', array(':id' => 'edit-action'));
    $this->assertEqual(count($options), 2);
    $this->assertOption('edit-action', 'node_make_sticky_action');
    $this->assertOption('edit-action', 'node_make_unsticky_action');

    // Set up to exclude the sticky actions.
    $view = Views::getView('test_bulk_form');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['fields']['node_bulk_form']['include_exclude'] = 'exclude';
    $view->save();

    $this->drupalGet('test_bulk_form');
    $this->assertNoOption('edit-action', 'node_make_sticky_action');
    $this->assertNoOption('edit-action', 'node_make_unsticky_action');

    // Check the default title.
    $this->drupalGet('test_bulk_form');
    $result = $this->xpath('//label[@for="edit-action"]');
    $this->assertEqual('With selection', (string) $result[0]);

    // Setup up a different bulk form title.
    $view = Views::getView('test_bulk_form');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['fields']['node_bulk_form']['action_title'] = 'Test title';
    $view->save();

    $this->drupalGet('test_bulk_form');
    $result = $this->xpath('//label[@for="edit-action"]');
    $this->assertEqual('Test title', (string) $result[0]);

    $this->drupalGet('test_bulk_form');
    // Call the node delete action.
    $edit = array();
    for ($i = 0; $i < 5; $i++) {
      $edit["node_bulk_form[$i]"] = TRUE;
    }
    $edit += array('action' => 'node_delete_action');
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    // Make sure we don't show an action message while we are still on the
    // confirmation page.
    $errors = $this->xpath('//div[contains(@class, "messages--status")]');
    $this->assertFalse($errors, 'No action message shown.');
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertText(t('Deleted 5 posts.'));
    // Check if we got redirected to the original page.
    $this->assertUrl('test_bulk_form');
  }

}
