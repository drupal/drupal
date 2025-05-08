<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Wizard;

use Drupal\views\Entity\View;

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
  protected static $configSchemaCheckerExclusions = [
    // To be able to test with the now invalid:
    // - `items_per_page: 'none'`
    // - `items_per_page: '5'`
    'block.block.views_block_items_per_page_test_with_historical_override',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the number of items per page.
   *
   * This should be removed from the `legacy` group in
   * https://drupal.org/i/3521221; see
   * \Drupal\views\Hook\ViewsHooks::blockPresave().
   *
   * @group legacy
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

    // Make sure the page display shows the 4 nodes we expect, and that they
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
    $block = $this->drupalPlaceBlock("views_block:{$view['id']}-block_1");

    // Asserts that the 3 newest articles are listed, which is the configuration
    // for the `block` display in the view. In other words: the `items_per_page`
    // setting in the `View` config entity is respected.
    $assert_3_newest_nodes = function () use ($node5, $node4, $node3, $node2, $node1, $page_node) {
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
    };
    self::assertSame(4, View::load($view['id'])->toArray()['display']['default']['display_options']['pager']['options']['items_per_page']);
    self::assertSame(3, View::load($view['id'])->toArray()['display']['block_1']['display_options']['pager']['options']['items_per_page']);
    self::assertArrayNotHasKey('items_per_page', $block->get('settings'));
    $assert_3_newest_nodes();
    $block->delete();

    // Because the `allow[items_per_page]` checkbox is checked, it is allowed to
    // override the `items_per_page` setting for the Views's `block` display,
    // and is actually respected. Valid values are `null` ("do not override")
    // and a positive integer.
    $block = $this->drupalPlaceBlock("views_block:{$view['id']}-block_1", [
      'items_per_page' => NULL,
    ]);
    self::assertSame(4, View::load($view['id'])->toArray()['display']['default']['display_options']['pager']['options']['items_per_page']);
    self::assertSame(3, View::load($view['id'])->toArray()['display']['block_1']['display_options']['pager']['options']['items_per_page']);
    self::assertNull($block->get('settings')['items_per_page']);
    $assert_3_newest_nodes();
    $block->delete();

    $block = $this->drupalPlaceBlock("views_block:{$view['id']}-block_1", [
      'items_per_page' => 5,
    ]);
    self::assertSame(4, View::load($view['id'])->toArray()['display']['default']['display_options']['pager']['options']['items_per_page']);
    self::assertSame(3, View::load($view['id'])->toArray()['display']['block_1']['display_options']['pager']['options']['items_per_page']);
    self::assertSame(5, $block->get('settings')['items_per_page']);
    $this->drupalGet('user');
    foreach ([$node5, $node4, $node3, $node2, $node1] as $node) {
      $this->assertSession()->pageTextContains($node->label());
    }
    $block->delete();

    // Finally: set `items_per_page: 'none'`, which is the predecessor of
    // `items_per_page: null`. This must continue to work as before even if the
    // configuration is no longer considered valid, because otherwise we risk
    // breaking e.g. blocks placed using Layout Builder.
    // @todo Delete in https://www.drupal.org/project/drupal/issues/3521221.
    $block = $this->drupalPlaceBlock("views_block:{$view['id']}-block_1", [
      'id' => 'views_block_items_per_page_test_with_historical_override',
    ]);
    // Explicitly set the `items_per_page` setting to a string without casting.
    // It should be changed to NULL by the pre-save hook.
    // @see \Drupal\views\Hook\ViewsHooks::blockPresave()
    $block->set('settings', [
      'items_per_page' => 'none',
    ])->trustData()->save();
    $this->expectDeprecation('Saving a views block with "none" items per page is deprecated in drupal:11.2.0 and removed in drupal:12.0.0. To use the items per page defined by the view, use NULL. See https://www.drupal.org/node/3522240');
    self::assertNull($block->get('settings')['items_per_page']);
    self::assertSame(4, View::load($view['id'])->toArray()['display']['default']['display_options']['pager']['options']['items_per_page']);
    self::assertSame(3, View::load($view['id'])->toArray()['display']['block_1']['display_options']['pager']['options']['items_per_page']);
    $assert_3_newest_nodes();
    $block->delete();

    // Truly finally: set `items_per_page: '5'`, because for the same reason as
    // above, blocks placed using Layout Builder may still have stale settings.
    $block = $this->drupalPlaceBlock("views_block:{$view['id']}-block_1", [
      'id' => 'views_block_items_per_page_test_with_historical_override',
    ]);
    // Explicitly set the `items_per_page` setting to a string without casting.
    $block->set('settings', [
      'items_per_page' => '5',
    ])->trustData()->save();
    self::assertSame('5', $block->get('settings')['items_per_page']);
    self::assertSame(4, View::load($view['id'])->toArray()['display']['default']['display_options']['pager']['options']['items_per_page']);
    self::assertSame(3, View::load($view['id'])->toArray()['display']['block_1']['display_options']['pager']['options']['items_per_page']);
    $this->drupalGet('user');
    foreach ([$node5, $node4, $node3, $node2, $node1] as $node) {
      $this->assertSession()->pageTextContains($node->label());
    }
  }

}
