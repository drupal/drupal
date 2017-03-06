<?php

namespace Drupal\views\Tests\Wizard;

/**
 * Tests the ability of the views wizard to specify the number of items per
 * page.
 *
 * @group views
 */
class ItemsPerPageTest extends WizardTestBase {

  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the number of items per page.
   */
  public function testItemsPerPage() {
    $this->drupalCreateContentType(['type' => 'article']);

    // Create articles, each with a different creation time so that we can do a
    // meaningful sort.
    $node1 = $this->drupalCreateNode(['type' => 'article', 'created' => REQUEST_TIME]);
    $node2 = $this->drupalCreateNode(['type' => 'article', 'created' => REQUEST_TIME + 1]);
    $node3 = $this->drupalCreateNode(['type' => 'article', 'created' => REQUEST_TIME + 2]);
    $node4 = $this->drupalCreateNode(['type' => 'article', 'created' => REQUEST_TIME + 3]);
    $node5 = $this->drupalCreateNode(['type' => 'article', 'created' => REQUEST_TIME + 4]);

    // Create a page. This should never appear in the view created below.
    $page_node = $this->drupalCreateNode(['type' => 'page', 'created' => REQUEST_TIME + 2]);

    // Create a view that sorts newest first, and shows 4 items in the page and
    // 3 in the block.
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
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
    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));
    $this->drupalGet($view['page[path]']);
    $this->assertResponse(200);

    // Make sure the page display shows the nodes we expect, and that they
    // appear in the expected order.
    $this->assertUrl($view['page[path]']);
    $this->assertText($view['page[title]']);
    $content = $this->getRawContent();
    $this->assertText($node5->label());
    $this->assertText($node4->label());
    $this->assertText($node3->label());
    $this->assertText($node2->label());
    $this->assertNoText($node1->label());
    $this->assertNoText($page_node->label());
    $pos5 = strpos($content, $node5->label());
    $pos4 = strpos($content, $node4->label());
    $pos3 = strpos($content, $node3->label());
    $pos2 = strpos($content, $node2->label());
    $this->assertTrue($pos5 < $pos4 && $pos4 < $pos3 && $pos3 < $pos2, 'The nodes appear in the expected order in the page display.');

    // Confirm that the block is listed in the block administration UI.
    $this->drupalGet('admin/structure/block/list/' . $this->config('system.theme')->get('default'));
    $this->clickLinkPartialName('Place block');
    $this->assertText($view['label']);

    // Place the block, visit a page that displays the block, and check that the
    // nodes we expect appear in the correct order.
    $this->drupalPlaceBlock("views_block:{$view['id']}-block_1");

    $this->drupalGet('user');
    $content = $this->getRawContent();
    $this->assertText($node5->label());
    $this->assertText($node4->label());
    $this->assertText($node3->label());
    $this->assertNoText($node2->label());
    $this->assertNoText($node1->label());
    $this->assertNoText($page_node->label());
    $pos5 = strpos($content, $node5->label());
    $pos4 = strpos($content, $node4->label());
    $pos3 = strpos($content, $node3->label());
    $this->assertTrue($pos5 < $pos4 && $pos4 < $pos3, 'The nodes appear in the expected order in the block display.');
  }

}
