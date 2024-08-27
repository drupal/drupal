<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests views bulk operation selection.
 *
 * @group views
 */
class ViewsBulkTest extends ViewTestBase {

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalCreateContentType(['type' => 'page']);
    $this->adminUser = $this->createUser(['bypass node access', 'administer nodes', 'access content overview']);
  }

  /**
   * Tests bulk selection.
   */
  public function testBulkSelection(): void {

    // Create first node, set updated time to the past.
    $node_1 = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'The first node',
      'changed' => \Drupal::time()->getRequestTime() - 180,
    ]);

    // Login as administrator and go to admin/content.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content');
    $this->assertSession()->pageTextContains($node_1->getTitle());

    // Create second node now that the admin overview has been rendered.
    $node_2 = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'The second node',
      'changed' => \Drupal::time()->getRequestTime() - 120,
    ]);

    // Select the node deletion action.
    $action_select = $this->getSession()->getPage()->findField('edit-action');
    $action_select_name = $action_select->getAttribute('name');
    $this->getSession()->getPage()->selectFieldOption($action_select_name, 'node_delete_action');

    // Now click 'Apply to selected items' and assert the first node is selected
    // on the confirm form.
    $this->submitForm(['node_bulk_form[0]' => TRUE], 'Apply to selected items');
    $this->assertSession()->pageTextContains($node_1->getTitle());
    $this->assertSession()->pageTextNotContains($node_2->getTitle());

    // Change the pager limit to 2.
    $this->config('views.view.content')->set('display.default.display_options.pager.options.items_per_page', 2)->save();

    // Render the overview page again.
    $this->drupalGet('admin/content');

    // Create third node now that the admin overview has been rendered.
    $node_3 = $this->drupalCreateNode([
      'type' => 'page',
      'title' => 'The third node',
    ]);

    // Select the node deletion action.
    $this->getSession()->getPage()->selectFieldOption($action_select_name, 'node_delete_action');

    // Now click 'Apply to selected items' and assert the second node is
    // selected on the confirm form.
    $this->submitForm(['node_bulk_form[1]' => TRUE], 'Apply to selected items');
    $this->assertSession()->pageTextContains($node_1->getTitle());
    $this->assertSession()->pageTextNotContains($node_3->getTitle());
  }

}
