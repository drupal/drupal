<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Wizard;

/**
 * Tests that the views wizard can specify the number of items per page.
 *
 * @group views
 */
class ItemsPerPageTest extends WizardTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the number of items per page.
   */
  public function testItemsPerPage(): void {
    $this->drupalCreateContentType(['type' => 'article']);

    // Create articles, each with a different creation time so that we can do a
    // meaningful sort.
    $node1 = $this->drupalCreateNode(['type' => 'article', 'created' => \Drupal::time()->getRequestTime()]);
    $node2 = $this->drupalCreateNode(['type' => 'article', 'created' => \Drupal::time()->getRequestTime() + 1]);
    $node3 = $this->drupalCreateNode(['type' => 'article', 'created' => \Drupal::time()->getRequestTime() + 2]);
    $node4 = $this->drupalCreateNode(['type' => 'article', 'created' => \Drupal::time()->getRequestTime() + 3]);
    $node5 = $this->drupalCreateNode(['type' => 'article', 'created' => \Drupal::time()->getRequestTime() + 4]);

    // Create a page. This should never appear in the view created below.
    $page_node = $this->drupalCreateNode(['type' => 'page', 'created' => \Drupal::time()->getRequestTime() + 2]);

    // Create a view that sorts newest first, and shows 4 items in the page and
    // 3 in the block.
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = $this->randomMachineName(16);
    $view['description'] = $this->randomMachineName(16);
    $view['show[wizard_key]'] = 'node';
    $view['show[type]'] = 'article';
    $view['show[sort]'] = 'node_field_data-created:DESC';
    $view['page[create]'] = 1;
    $view['page[title]'] = $this->randomMachineName(16);
    $view['page[path]'] = $this->randomMachineName(16);
    $view['page[items_per_page]'] = 4;
    $view['block[create]'] = 1;
    $view['block[title]'] = $this->randomMachineName(16);
    $view['block[items_per_page]'] = 3;
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($view, 'Save and edit');

    // Uncheck items per page in block settings.
    $this->drupalGet($this->getSession()->getCurrentUrl() . '/edit/block_1');
    $this->clickLink('Items per page');
    $this->assertSession()->checkboxChecked('allow[items_per_page]');
    $this->getSession()->getPage()->uncheckField('allow[items_per_page]');
    $this->getSession()->getPage()->pressButton('Apply');
    $this->getSession()->getPage()->pressButton('Save');

    // Check items per page in block settings.
    $this->drupalGet('admin/structure/views/nojs/display/' . $view['id'] . '/block_1/allow');
    $this->assertSession()->checkboxNotChecked('allow[items_per_page]');
    $this->getSession()->getPage()->checkField('allow[items_per_page]');
    $this->getSession()->getPage()->pressButton('Apply');
    $this->getSession()->getPage()->pressButton('Save');

    // Ensure that items per page checkbox remains checked.
    $this->clickLink('Items per page');
    $this->assertSession()->checkboxChecked('allow[items_per_page]');

    $this->drupalGet($view['page[path]']);
    $this->assertSession()->statusCodeEquals(200);

    // Make sure the page display shows the nodes we expect, and that they
    // appear in the expected order.
    $this->assertSession()->addressEquals($view['page[path]']);
    $this->assertSession()->pageTextContains($view['page[title]']);
    $content = $this->getSession()->getPage()->getContent();
    $this->assertSession()->pageTextContains($node5->label());
    $this->assertSession()->pageTextContains($node4->label());
    $this->assertSession()->pageTextContains($node3->label());
    $this->assertSession()->pageTextContains($node2->label());
    $this->assertSession()->pageTextNotContains($node1->label());
    $this->assertSession()->pageTextNotContains($page_node->label());
    $pos5 = strpos($content, $node5->label());
    $pos4 = strpos($content, $node4->label());
    $pos3 = strpos($content, $node3->label());
    $pos2 = strpos($content, $node2->label());
    $this->assertGreaterThan($pos5, $pos4);
    $this->assertGreaterThan($pos4, $pos3);
    $this->assertGreaterThan($pos3, $pos2);

    // Confirm that the block is listed in the block administration UI.
    $this->drupalGet('admin/structure/block/list/' . $this->config('system.theme')->get('default'));
    $this->clickLink('Place block');
    $this->assertSession()->pageTextContains($view['label']);

    // Place the block, visit a page that displays the block, and check that the
    // nodes we expect appear in the correct order.
    $this->drupalPlaceBlock("views_block:{$view['id']}-block_1");

    $this->drupalGet('user');
    $content = $this->getSession()->getPage()->getContent();
    $this->assertSession()->pageTextContains($node5->label());
    $this->assertSession()->pageTextContains($node4->label());
    $this->assertSession()->pageTextContains($node3->label());
    $this->assertSession()->pageTextNotContains($node2->label());
    $this->assertSession()->pageTextNotContains($node1->label());
    $this->assertSession()->pageTextNotContains($page_node->label());
    $pos5 = strpos($content, $node5->label());
    $pos4 = strpos($content, $node4->label());
    $pos3 = strpos($content, $node3->label());
    $this->assertGreaterThan($pos5, $pos4);
    $this->assertGreaterThan($pos4, $pos3);
  }

}
