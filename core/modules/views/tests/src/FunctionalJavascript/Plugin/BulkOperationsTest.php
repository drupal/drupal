<?php

namespace Drupal\Tests\views\FunctionalJavascript\Plugin;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the bulk operations.
 *
 * @group views
 */
class BulkOperationsTest extends WebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable AJAX on the /admin/content View.
    \Drupal::configFactory()->getEditable('views.view.content')
      ->set('display.default.display_options.use_ajax', TRUE)
      ->save();

    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalLogin($this->createUser(['bypass node access', 'administer nodes', 'access content overview']));
  }

  public function testBulkOperations() {
    $node_1 = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'The first node',
      'changed' => \Drupal::time()->getRequestTime() - 180,
    ]);
    $node_2 = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'The second node',
      'changed' => \Drupal::time()->getRequestTime() - 120,
    ]);

    // Login as administrator and go to admin/content.
    $this->drupalGet('admin/content');
    $this->assertSession()->pageTextContains($node_1->getTitle());

    // Filter the list.
    $this->assertSession()->pageTextContains($node_1->getTitle());
    $this->assertSession()->pageTextContains($node_2->getTitle());
    $this->submitForm(['title' => 'The first node'], 'Filter');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($node_1->getTitle());
    $this->assertSession()->pageTextNotContains($node_2->getTitle());

    // Select the node deletion action.
    $action_select = $this->getSession()->getPage()->find('css', '[data-drupal-selector="edit-action"]');
    $action_select_name = $action_select->getAttribute('name');
    $this->getSession()->getPage()->selectFieldOption($action_select_name, 'node_delete_action');

    // Now click 'Apply to selected items' and assert the first node is selected
    // on the confirm form.
    $this->submitForm(['node_bulk_form[0]' => TRUE], 'Apply to selected items');
    $this->assertSession()->pageTextContains($node_1->getTitle());
    $this->assertSession()->pageTextNotContains($node_2->getTitle());
    $this->getSession()->getPage()->pressButton('Delete');

    // Confirm that the first node was deleted.
    $this->assertSession()->pageTextNotContains($node_1->getTitle());
    $this->assertSession()->pageTextNotContains($node_2->getTitle());

    // Ensure that assets are loaded on the page. This confirms that the page
    // was loaded without ajax state.
    $this->assertSession()->responseContains('/core/misc/ajax.js');

    // Confirm that second node exists.
    $this->submitForm([], 'Reset');
    $this->assertSession()->pageTextContains($node_2->getTitle());

    // Select the node unpublish action.
    $action_select = $this->getSession()->getPage()->find('css', '[data-drupal-selector="edit-action"]');
    $action_select_name = $action_select->getAttribute('name');
    $this->getSession()->getPage()->selectFieldOption($action_select_name, 'node_unpublish_action');

    // Ensure that assets are loaded on the page. This confirms that the page
    // was loaded without ajax state.
    $this->assertSession()->responseContains('/core/misc/ajax.js');
  }

}
