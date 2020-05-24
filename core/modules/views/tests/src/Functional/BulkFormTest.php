<?php

namespace Drupal\Tests\views\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;
use Drupal\views\Views;

/**
 * Tests the views bulk form test.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\field\BulkForm
 */
class BulkFormTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node', 'action_bulk_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the bulk form.
   */
  public function testBulkForm() {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // First, test an empty bulk form with the default style plugin to make sure
    // the empty region is rendered correctly.
    $this->drupalGet('test_bulk_form_empty');
    $this->assertText(t('This view is empty.'), 'Empty text found on empty bulk form.');

    $nodes = [];
    for ($i = 0; $i < 10; $i++) {
      // Ensure nodes are sorted in the same order they are inserted in the
      // array.
      $timestamp = REQUEST_TIME - $i;
      $nodes[] = $this->drupalCreateNode([
        'title' => 'Node ' . $i,
        'sticky' => FALSE,
        'created' => $timestamp,
        'changed' => $timestamp,
      ]);
    }

    $this->drupalGet('test_bulk_form');

    // Test that the views edit header appears first.
    $first_form_element = $this->xpath('//form/div[1][@id = :id]', [':id' => 'edit-header']);
    $this->assertNotEmpty($first_form_element, 'The views form edit header appears first.');

    $this->assertFieldById('edit-action', NULL, 'The action select field appears.');

    // Make sure a checkbox appears on all rows.
    $edit = [];
    for ($i = 0; $i < 10; $i++) {
      $this->assertFieldById('edit-node-bulk-form-' . $i, NULL, new FormattableMarkup('The checkbox on row @row appears.', ['@row' => $i]));
      $edit["node_bulk_form[$i]"] = TRUE;
    }

    // Log in as a user with 'administer nodes' permission to have access to the
    // bulk operation.
    $this->drupalCreateContentType(['type' => 'page']);
    $admin_user = $this->drupalCreateUser(['administer nodes', 'edit any page content', 'delete any page content']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('test_bulk_form');

    // Set all nodes to sticky and check that.
    $edit += ['action' => 'node_make_sticky_action'];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));

    foreach ($nodes as $node) {
      $changed_node = $node_storage->load($node->id());
      $this->assertTrue($changed_node->isSticky(), new FormattableMarkup('Node @nid got marked as sticky.', ['@nid' => $node->id()]));
    }

    $this->assertText('Make content sticky was applied to 10 items.');

    // Unpublish just one node.
    $node = $node_storage->load($nodes[0]->id());
    $this->assertTrue($node->isPublished(), 'The node is published.');

    $edit = ['node_bulk_form[0]' => TRUE, 'action' => 'node_unpublish_action'];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));

    $this->assertText('Unpublish content was applied to 1 item.');

    // Load the node again.
    $node_storage->resetCache([$node->id()]);
    $node = $node_storage->load($node->id());
    $this->assertFalse($node->isPublished(), 'A single node has been unpublished.');

    // The second node should still be published.
    $node_storage->resetCache([$nodes[1]->id()]);
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
    $options = $this->xpath('//select[@id=:id]/option', [':id' => 'edit-action']);
    $this->assertCount(2, $options);
    $this->assertSession()->optionExists('edit-action', 'node_make_sticky_action');
    $this->assertSession()->optionExists('edit-action', 'node_make_unsticky_action');

    // Set up to exclude the sticky actions.
    $view = Views::getView('test_bulk_form');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['fields']['node_bulk_form']['include_exclude'] = 'exclude';
    $view->save();

    $this->drupalGet('test_bulk_form');
    $this->assertSession()->optionNotExists('edit-action', 'node_make_sticky_action');
    $this->assertSession()->optionNotExists('edit-action', 'node_make_unsticky_action');

    // Check the default title.
    $this->drupalGet('test_bulk_form');
    $result = $this->xpath('//label[@for="edit-action"]');
    $this->assertEqual('Action', $result[0]->getText());

    // Setup up a different bulk form title.
    $view = Views::getView('test_bulk_form');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['fields']['node_bulk_form']['action_title'] = 'Test title';
    $view->save();

    $this->drupalGet('test_bulk_form');
    $result = $this->xpath('//label[@for="edit-action"]');
    $this->assertEqual('Test title', $result[0]->getText());

    $this->drupalGet('test_bulk_form');
    // Call the node delete action.
    $edit = [];
    for ($i = 0; $i < 5; $i++) {
      $edit["node_bulk_form[$i]"] = TRUE;
    }
    $edit += ['action' => 'node_delete_action'];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    // Make sure we don't show an action message while we are still on the
    // confirmation page.
    $errors = $this->xpath('//div[contains(@class, "messages--status")]');
    $this->assertEmpty($errors, 'No action message shown.');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertText(t('Deleted 5 content items.'));
    // Check if we got redirected to the original page.
    $this->assertUrl('test_bulk_form');

    // Test that the bulk form works when a node gets deleted by another user
    // before the loaded bulk form can be used.
    $this->drupalGet('test_bulk_form');
    // Now delete the node we want to delete with the bulk form.
    $link = $this->getSession()->getPage()->findLink($nodes[6]->label());
    $checkbox = $link->getParent()->getParent()->find('css', 'input');
    $nodes[6]->delete();
    $edit = [
      $checkbox->getAttribute('name') => TRUE,
      'action' => 'node_delete_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    // Make sure we just return to the bulk view with no warnings.
    $this->assertUrl('test_bulk_form');
    $errors = $this->xpath('//div[contains(@class, "messages--status")]');
    $this->assertEmpty($errors, 'No action message shown.');

    // Test that the bulk form works when multiple nodes are selected
    // but one of the selected nodes are already deleted by another user before
    // the loaded bulk form was submitted.
    $this->drupalGet('test_bulk_form');
    // Call the node delete action.
    $nodes[7]->delete();
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'node_bulk_form[1]' => TRUE,
      'action' => 'node_delete_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    // Make sure we don't show an action message while we are still on the
    // confirmation page.
    $errors = $this->xpath('//div[contains(@class, "messages--status")]');
    $this->assertEmpty($errors, 'No action message shown.');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertText(t('Deleted 1 content item.'));

    // Test that the bulk form works when multiple nodes are selected
    // but all of the selected nodes are already deleted
    //  by another user before the loaded bulk form was submitted.
    $this->drupalGet('test_bulk_form');
    // Call the node delete action.
    foreach ($nodes as $key => $node) {
      $node->delete();
    }
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_delete_action',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply to selected items'));
    $this->assertText('No content selected.');
  }

}
