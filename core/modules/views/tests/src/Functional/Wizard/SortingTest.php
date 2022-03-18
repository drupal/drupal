<?php

namespace Drupal\Tests\views\Functional\Wizard;

/**
 * Tests the ability of the views wizard to create views with sorts.
 *
 * @group views
 */
class SortingTest extends WizardTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the sorting functionality.
   */
  public function testSorting() {
    // Create nodes, each with a different creation time so that we can do a
    // meaningful sort.
    $this->drupalCreateContentType(['type' => 'page']);
    $node1 = $this->drupalCreateNode(['created' => REQUEST_TIME]);
    $node2 = $this->drupalCreateNode(['created' => REQUEST_TIME + 1]);
    $node3 = $this->drupalCreateNode(['created' => REQUEST_TIME + 2]);

    // Create a view that sorts oldest first.
    $view1 = [];
    $view1['label'] = $this->randomMachineName(16);
    $view1['id'] = strtolower($this->randomMachineName(16));
    $view1['description'] = $this->randomMachineName(16);
    $view1['show[sort]'] = 'node_field_data-created:ASC';
    $view1['page[create]'] = 1;
    $view1['page[title]'] = $this->randomMachineName(16);
    $view1['page[path]'] = $this->randomMachineName(16);
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view1, 'Save and edit');
    $this->drupalGet($view1['page[path]']);
    $this->assertSession()->statusCodeEquals(200);

    // Make sure the view shows the nodes in the expected order.
    $this->assertSession()->addressEquals($view1['page[path]']);
    $this->assertSession()->pageTextContains($view1['page[title]']);
    $content = $this->getSession()->getPage()->getContent();
    $this->assertSession()->pageTextContains($node1->label());
    $this->assertSession()->pageTextContains($node2->label());
    $this->assertSession()->pageTextContains($node3->label());
    $pos1 = strpos($content, $node1->label());
    $pos2 = strpos($content, $node2->label());
    $pos3 = strpos($content, $node3->label());
    $this->assertGreaterThan($pos1, $pos2);
    $this->assertGreaterThan($pos2, $pos3);

    // Create a view that sorts newest first.
    $view2 = [];
    $view2['label'] = $this->randomMachineName(16);
    $view2['id'] = strtolower($this->randomMachineName(16));
    $view2['description'] = $this->randomMachineName(16);
    $view2['show[sort]'] = 'node_field_data-created:DESC';
    $view2['page[create]'] = 1;
    $view2['page[title]'] = $this->randomMachineName(16);
    $view2['page[path]'] = $this->randomMachineName(16);
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view2, 'Save and edit');
    $this->drupalGet($view2['page[path]']);
    $this->assertSession()->statusCodeEquals(200);

    // Make sure the view shows the nodes in the expected order.
    $this->assertSession()->addressEquals($view2['page[path]']);
    $this->assertSession()->pageTextContains($view2['page[title]']);
    $content = $this->getSession()->getPage()->getContent();
    $this->assertSession()->pageTextContains($node3->label());
    $this->assertSession()->pageTextContains($node2->label());
    $this->assertSession()->pageTextContains($node1->label());
    $pos3 = strpos($content, $node3->label());
    $pos2 = strpos($content, $node2->label());
    $pos1 = strpos($content, $node1->label());
    $this->assertGreaterThan($pos3, $pos2);
    $this->assertGreaterThan($pos2, $pos1);
  }

}
