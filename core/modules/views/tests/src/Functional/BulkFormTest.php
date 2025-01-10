<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\views\Views;

/**
 * Tests the views bulk form test.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\field\BulkForm
 */
class BulkFormTest extends BrowserTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'action_bulk_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Log in as a user with 'administer nodes' permission to have access to the
    // bulk operation.
    $this->drupalCreateContentType(['type' => 'page']);
    $admin_user = $this->drupalCreateUser([
      'administer nodes',
      'edit any page content',
      'delete any page content',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the bulk form.
   */
  public function testBulkForm(): void {
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');

    // First, test an empty bulk form with the default style plugin to make sure
    // the empty region is rendered correctly.
    $this->drupalGet('test_bulk_form_empty');
    $this->assertSession()->pageTextContains('This view is empty.');

    $nodes = [];
    for ($i = 0; $i < 10; $i++) {
      // Ensure nodes are sorted in the same order they are inserted in the
      // array.
      $timestamp = \Drupal::time()->getRequestTime() - $i;
      $nodes[] = $this->drupalCreateNode([
        'title' => 'Node ' . $i,
        'sticky' => FALSE,
        'created' => $timestamp,
        'changed' => $timestamp,
      ]);
    }

    $this->drupalGet('test_bulk_form');

    // Test that the views edit header appears first.
    $this->assertSession()->elementExists('xpath', '//form/div[1][@id = "edit-header"]');

    $this->assertSession()->fieldExists('edit-action');

    // Make sure a checkbox appears on all rows.
    $edit = [];
    for ($i = 0; $i < 10; $i++) {
      $this->assertSession()->fieldExists('edit-node-bulk-form-' . $i);
      $edit["node_bulk_form[$i]"] = TRUE;
    }

    $this->drupalGet('test_bulk_form');

    // Set all nodes to sticky and check that.
    $edit += ['action' => 'node_make_sticky_action'];
    $this->submitForm($edit, 'Apply to selected items');

    foreach ($nodes as $node) {
      $changed_node = $node_storage->load($node->id());
      $this->assertTrue($changed_node->isSticky(), "Node {$node->id()} got marked as sticky.");
    }

    $this->assertSession()->pageTextContains('Make content sticky was applied to 10 items.');

    // Unpublish just one node.
    $node = $node_storage->load($nodes[0]->id());
    $this->assertTrue($node->isPublished(), 'The node is published.');

    $edit = ['node_bulk_form[0]' => TRUE, 'action' => 'node_unpublish_action'];
    $this->submitForm($edit, 'Apply to selected items');

    $this->assertSession()->pageTextContains('Unpublish content was applied to 1 item.');

    // Load the node again.
    $node = $node_storage->load($node->id());
    $this->assertFalse($node->isPublished(), 'A single node has been unpublished.');

    // The second node should still be published.
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
    $options = $this->assertSession()->selectExists('edit-action')->findAll('css', 'option');
    $this->assertCount(3, $options);
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
    $this->assertSession()->elementTextEquals('xpath', '//label[@for="edit-action"]', 'Action');

    // There should be an error message if no action is selected.
    $edit = ['node_bulk_form[0]' => TRUE, 'action' => ''];
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->pageTextContains('No Action option selected.');

    // Setup up a different bulk form title.
    $view = Views::getView('test_bulk_form');
    $display = &$view->storage->getDisplay('default');
    $display['display_options']['fields']['node_bulk_form']['action_title'] = 'Test title';
    $view->save();

    $this->drupalGet('test_bulk_form');
    $this->assertSession()->elementTextEquals('xpath', '//label[@for="edit-action"]', 'Test title');

    // The error message when no action is selected should reflect the new form
    // title.
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->pageTextContains('No Test title option selected.');

    $this->drupalGet('test_bulk_form');
    // Call the node delete action.
    $edit = [];
    for ($i = 0; $i < 5; $i++) {
      $edit["node_bulk_form[$i]"] = TRUE;

      // $nodes[0] was unpublished above, so the bulk form displays only
      // $nodes[1] - $nodes[9]. Remove deleted items from $nodes to prevent
      // deleting them twice at the end of this test method.
      unset($nodes[$i + 1]);
    }
    $edit += ['action' => 'node_delete_action'];
    $this->submitForm($edit, 'Apply to selected items');
    // Make sure we don't show an action message while we are still on the
    // confirmation page.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "messages--status")]');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('Deleted 5 content items.');
    // Check if we got redirected to the original page.
    $this->assertSession()->addressEquals('test_bulk_form');

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
    $this->submitForm($edit, 'Apply to selected items');

    // Remove deleted items from $nodes to prevent deleting them twice at the
    // end of this test method.
    unset($nodes[6]);

    // Make sure we just return to the bulk view with no warnings.
    $this->assertSession()->addressEquals('test_bulk_form');
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "messages--status")]');

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
    $this->submitForm($edit, 'Apply to selected items');

    // Remove deleted items from $nodes to prevent deleting them twice at the
    // end of this test method.
    unset($nodes[7], $nodes[8]);

    // Make sure we don't show an action message while we are still on the
    // confirmation page.
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "messages--status")]');
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains('Deleted 1 content item.');

    // Test that the bulk form works when multiple nodes are selected
    // but all of the selected nodes are already deleted
    // by another user before the loaded bulk form was submitted.
    $this->drupalGet('test_bulk_form');
    // Call the node delete action.
    foreach ($nodes as $node) {
      $node->delete();
    }
    $edit = [
      'node_bulk_form[0]' => TRUE,
      'action' => 'node_delete_action',
    ];
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->pageTextContains('No content selected.');
  }

  /**
   * Tests that route parameters are passed to the confirmation form route.
   */
  public function testConfirmRouteWithParameters(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert = $this->assertSession();

    $node = $this->createNode();
    // Access the view page.
    $this->drupalGet('/node/' . $node->id() . '/test_bulk_form');

    // Select a node and perform the 'Test action'.
    $page->checkField('node_bulk_form[0]');
    $page->selectFieldOption('Action', 'Test action');
    $page->pressButton('Apply to selected items');

    // Check that we've been landed on the confirmation form.
    $assert->pageTextContains('Do you agree?');
    // Check that route parameters were passed to the confirmation from route.
    $assert->addressEquals('/node/' . $node->id() . '/confirm');
  }

}
