<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Functional\Views;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\Tests\block\Functional\AssertBlockAppearsTrait;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Entity\View;
use Drupal\views\Views;
use Drupal\Core\Template\Attribute;

/**
 * Tests the block display plugin.
 *
 * @group block
 * @see \Drupal\views\Plugin\views\display\Block
 */
class DisplayBlockTest extends ViewTestBase {

  use AssertPageCacheContextsAndTagsTrait;
  use AssertBlockAppearsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'block_test_views',
    'test_page_test',
    'contextual',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view_block', 'test_view_block2'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['block_test_views']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();
  }

  /**
   * Tests default and content block categories.
   */
  public function testBlockCategory(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'administer views',
      'administer blocks',
    ]));

    // Create a new view in the UI.
    $edit = [];
    $edit['label'] = $this->randomString();
    $edit['id'] = $this->randomMachineName();
    $edit['show[wizard_key]'] = 'standard:views_test_data';
    $edit['description'] = $this->randomString();
    $edit['block[create]'] = TRUE;
    $edit['block[style][row_plugin]'] = 'fields';
    $this->drupalGet('admin/structure/views/add');
    $this->submitForm($edit, 'Save and edit');

    $pattern = '//tr[.//td[text()=:category] and .//td//a[contains(@href, :href)]]';
    $arguments = [
      ':href' => Url::fromRoute('block.admin_add', [
        'plugin_id' => 'views_block:' . $edit['id'] . '-block_1',
        'theme' => 'stark',
      ])->toString(),
      ':category' => 'Lists (Views)',
    ];

    // Test that the block was given a default category corresponding to its
    // base table.
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Place block');
    $this->assertSession()->elementExists('xpath', $this->assertSession()->buildXPathQuery($pattern, $arguments));

    // Duplicate the block before changing the category.
    $this->drupalGet('admin/structure/views/view/' . $edit['id'] . '/edit/block_1');
    $this->submitForm([], 'Duplicate Block');
    $this->assertSession()->addressEquals('admin/structure/views/view/' . $edit['id'] . '/edit/block_2');

    // Change the block category to a random string.
    $this->drupalGet('admin/structure/views/view/' . $edit['id'] . '/edit/block_1');
    $this->assertSession()->elementTextEquals('named', ['id', 'views-block-1-block-category'], 'Lists (Views)');
    $this->clickLink('Lists (Views)');
    $category = $this->randomString();
    $this->submitForm(['block_category' => $category], 'Apply');

    // Duplicate the block after changing the category.
    $this->submitForm([], 'Duplicate Block');
    $this->assertSession()->addressEquals('admin/structure/views/view/' . $edit['id'] . '/edit/block_3');

    $this->submitForm([], 'Save');

    // Test that the blocks are listed under the correct categories.
    $arguments[':category'] = $category;
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Place block');
    $this->assertSession()->elementExists('xpath', $this->assertSession()->buildXPathQuery($pattern, $arguments));

    // Test that the first duplicated test block remains in the original
    // category.
    $arguments = [
      ':href' => Url::fromRoute('block.admin_add', [
        'plugin_id' => 'views_block:' . $edit['id'] . '-block_2',
        'theme' => 'stark',
      ])->toString(),
      ':category' => 'Lists (Views)',
    ];
    $this->assertSession()->elementExists('xpath', $this->assertSession()->buildXPathQuery($pattern, $arguments));

    // Test that the second duplicated test block appears in the custom
    // category.
    $arguments = [
      ':href' => Url::fromRoute('block.admin_add', [
        'plugin_id' => 'views_block:' . $edit['id'] . '-block_3',
        'theme' => 'stark',
      ])->toString(),
      ':category' => $category,
    ];
    $this->assertSession()->elementExists('xpath', $this->assertSession()->buildXPathQuery($pattern, $arguments));
  }

  /**
   * Tests removing a block display.
   */
  public function testDeleteBlockDisplay(): void {
    // To test all combinations possible we first place create two instances
    // of the block display of the first view.
    $block_1 = $this->drupalPlaceBlock('views_block:test_view_block-block_1', ['label' => 'test_view_block-block_1:1']);
    $block_2 = $this->drupalPlaceBlock('views_block:test_view_block-block_1', ['label' => 'test_view_block-block_1:2']);

    // Then we add one instance of blocks for each of the two displays of the
    // second view.
    $block_3 = $this->drupalPlaceBlock('views_block:test_view_block2-block_1', ['label' => 'test_view_block2-block_1']);
    $block_4 = $this->drupalPlaceBlock('views_block:test_view_block2-block_2', ['label' => 'test_view_block2-block_2']);

    $this->drupalGet('test-page');
    $this->assertBlockAppears($block_1);
    $this->assertBlockAppears($block_2);
    $this->assertBlockAppears($block_3);
    $this->assertBlockAppears($block_4);

    $block_storage = $this->container->get('entity_type.manager')->getStorage('block');

    // Remove the block display, so both block entities from the first view
    // should both disappear.
    $view = Views::getView('test_view_block');
    $view->initDisplay();
    $view->displayHandlers->remove('block_1');
    $view->storage->save();

    $this->assertNull($block_storage->load($block_1->id()), 'The block for this display was removed.');
    $this->assertNull($block_storage->load($block_2->id()), 'The block for this display was removed.');
    $this->assertNotEmpty($block_storage->load($block_3->id()), 'A block from another view was unaffected.');
    $this->assertNotEmpty($block_storage->load($block_4->id()), 'A block from another view was unaffected.');
    $this->drupalGet('test-page');
    $this->assertNoBlockAppears($block_1);
    $this->assertNoBlockAppears($block_2);
    $this->assertBlockAppears($block_3);
    $this->assertBlockAppears($block_4);

    // Remove the first block display of the second view and ensure the block
    // instance of the second block display still exists.
    $view = Views::getView('test_view_block2');
    $view->initDisplay();
    $view->displayHandlers->remove('block_1');
    $view->storage->save();

    $this->assertNull($block_storage->load($block_3->id()), 'The block for this display was removed.');
    $this->assertNotEmpty($block_storage->load($block_4->id()), 'A block from another display on the same view was unaffected.');
    $this->drupalGet('test-page');
    $this->assertNoBlockAppears($block_3);
    $this->assertBlockAppears($block_4);
  }

  /**
   * Tests the block form for a Views block.
   */
  public function testViewsBlockForm(): void {
    $this->drupalLogin($this->drupalCreateUser(['administer blocks']));
    $default_theme = $this->config('system.theme')->get('default');
    $this->drupalGet('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme);
    $this->assertSession()->fieldNotExists('label');
    // Test that the machine name field is hidden from display and has been
    // saved as expected from the default value.
    $this->assertSession()->fieldNotExists('edit-machine-name', NULL);

    // Save the block.
    $edit = ['region' => 'content'];
    $this->submitForm($edit, 'Save block');
    $storage = $this->container->get('entity_type.manager')->getStorage('block');
    $block = $storage->load($default_theme . '_views_block__test_view_block_block_1');
    // This will only return a result if our new block has been created with the
    // expected machine name.
    $this->assertNotEmpty($block, 'The expected block was loaded.');

    for ($i = 2; $i <= 3; $i++) {
      // Place the same block again and make sure we have a new ID.
      $this->drupalGet('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme);
      $this->submitForm($edit, 'Save block');
      $block = $storage->load($default_theme . '_views_block__test_view_block_block_1_' . $i);
      // This will only return a result if our new block has been created with the
      // expected machine name.
      $this->assertNotEmpty($block, 'The expected block was loaded.');
    }

    // Tests the override capability of items per page.
    $this->drupalGet('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme);
    $edit = ['region' => 'content'];
    $edit['settings[override][items_per_page]'] = 10;

    $this->drupalGet('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    $block = $storage->load($default_theme . '_views_block__test_view_block_block_1_4');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEquals(10, $config['items_per_page'], "'Items per page' is properly saved.");

    $edit['settings[override][items_per_page]'] = 5;
    $this->drupalGet('admin/structure/block/manage/' . $default_theme . '_views_block__test_view_block_block_1_4');
    $this->submitForm($edit, 'Save block');

    $block = $storage->load($default_theme . '_views_block__test_view_block_block_1_4');

    $config = $block->getPlugin()->getConfiguration();
    $this->assertEquals(5, $config['items_per_page'], "'Items per page' is properly saved.");

    // Tests the override of the label capability.
    $edit = ['region' => 'content'];
    $edit['settings[views_label_checkbox]'] = 1;
    $edit['settings[views_label]'] = 'Custom title';
    $this->drupalGet('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme);
    $this->submitForm($edit, 'Save block');

    $block = $storage->load($default_theme . '_views_block__test_view_block_block_1_5');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEquals('Custom title', $config['views_label'], "'Label' is properly saved.");
  }

  /**
   * Tests the actual rendering of the views block.
   */
  public function testBlockRendering(): void {
    // Create a block and set a custom title.
    $block = $this->drupalPlaceBlock('views_block:test_view_block-block_1', [
      'label' => 'test_view_block-block_1:1',
      'views_label' => 'Custom title',
      'region' => 'sidebar_first',
    ]);
    $block_title_xpath = $this->assertSession()->buildXPathQuery('//aside[contains(@class, "layout-sidebar-first")]//div[@id = :id]/h2', [
      ':id' => 'block-' . $block->id(),
    ]);
    $this->drupalGet('');
    $this->assertSession()->elementTextEquals('xpath', $block_title_xpath, 'Custom title');

    // Don't override the title anymore.
    $plugin = $block->getPlugin();
    $plugin->setConfigurationValue('views_label', '');
    $block->save();

    $this->drupalGet('');
    $this->assertSession()->elementTextEquals('xpath', $block_title_xpath, 'test_view_block');

    // Hide the title.
    $block->getPlugin()->setConfigurationValue('label_display', FALSE);
    $block->save();

    $this->drupalGet('');
    $this->assertSession()->elementNotExists('xpath', $block_title_xpath);

    $this->assertCacheTags(array_merge($block->getCacheTags(), ['block_view', 'config:block_list', 'config:system.site', 'config:views.view.test_view_block', 'http_response', 'CACHE_MISS_IF_UNCACHEABLE_HTTP_METHOD:form', 'rendered']));
  }

  /**
   * Tests the various test cases of empty block rendering.
   */
  public function testBlockEmptyRendering(): void {
    $url = new Url('test_page_test.test_page');
    // Remove all views_test_data entries.
    \Drupal::database()->truncate('views_test_data')->execute();
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('test_view_block');
    $view->invalidateCaches();

    $block = $this->drupalPlaceBlock('views_block:test_view_block-block_1', ['label' => 'test_view_block-block_1:1', 'views_label' => 'Custom title']);
    $block_xpath = $this->assertSession()->buildXPathQuery('//div[@id = :id]', [
      ':id' => 'block-' . $block->id(),
    ]);
    $this->drupalGet('');
    $this->assertSession()->elementsCount('xpath', $block_xpath, 1);

    $display = &$view->getDisplay('block_1');
    $display['display_options']['block_hide_empty'] = TRUE;
    $view->save();

    $this->drupalGet($url);
    $this->assertSession()->elementNotExists('xpath', $block_xpath);
    // Ensure that the view cacheability metadata is propagated even, for an
    // empty block.
    $this->assertCacheTags(array_merge($block->getCacheTags(), ['block_view', 'config:block_list', 'config:views.view.test_view_block', 'http_response', 'rendered']));
    $this->assertCacheContexts(['url.query_args:_wrapper_format']);

    // Add a header displayed on empty result.
    $display = &$view->getDisplay('block_1');
    $display['display_options']['defaults']['header'] = FALSE;
    $display['display_options']['header']['example'] = [
      'field' => 'area_text_custom',
      'id' => 'area_text_custom',
      'table' => 'views',
      'plugin_id' => 'text_custom',
      'content' => 'test header',
      'empty' => TRUE,
    ];
    $view->save();

    $this->drupalGet($url);
    $this->assertSession()->elementsCount('xpath', $block_xpath, 1);
    $this->assertCacheTags(array_merge($block->getCacheTags(), ['block_view', 'config:block_list', 'config:views.view.test_view_block', 'http_response', 'rendered']));
    $this->assertCacheContexts(['url.query_args:_wrapper_format']);

    // Hide the header on empty results.
    $display = &$view->getDisplay('block_1');
    $display['display_options']['defaults']['header'] = FALSE;
    $display['display_options']['header']['example'] = [
      'field' => 'area_text_custom',
      'id' => 'area_text_custom',
      'table' => 'views',
      'plugin_id' => 'text_custom',
      'content' => 'test header',
      'empty' => FALSE,
    ];
    $view->save();

    $this->drupalGet($url);
    $this->assertSession()->elementNotExists('xpath', $block_xpath);
    $this->assertCacheTags(array_merge($block->getCacheTags(), ['block_view', 'config:block_list', 'config:views.view.test_view_block', 'http_response', 'rendered']));
    $this->assertCacheContexts(['url.query_args:_wrapper_format']);

    // Add an empty text.
    $display = &$view->getDisplay('block_1');
    $display['display_options']['defaults']['empty'] = FALSE;
    $display['display_options']['empty']['example'] = [
      'field' => 'area_text_custom',
      'id' => 'area_text_custom',
      'table' => 'views',
      'plugin_id' => 'text_custom',
      'content' => 'test empty',
    ];
    $view->save();

    $this->drupalGet($url);
    $this->assertSession()->elementsCount('xpath', $block_xpath, 1);
    $this->assertCacheTags(array_merge($block->getCacheTags(), ['block_view', 'config:block_list', 'config:views.view.test_view_block', 'http_response', 'rendered']));
    $this->assertCacheContexts(['url.query_args:_wrapper_format']);
  }

  /**
   * Tests the contextual links on a Views block.
   */
  public function testBlockContextualLinks(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'administer views',
      'access contextual links',
      'administer blocks',
    ]));
    $block = $this->drupalPlaceBlock('views_block:test_view_block-block_1');
    $cached_block = $this->drupalPlaceBlock('views_block:test_view_block-block_1');
    $this->drupalGet('test-page');

    $id = 'block:block=' . $block->id() . ':langcode=en|entity.view.edit_form:view=test_view_block:location=block&name=test_view_block&display_id=block_1&langcode=en';
    $id_token = Crypt::hmacBase64($id, Settings::getHashSalt() . $this->container->get('private_key')->get());
    $cached_id = 'block:block=' . $cached_block->id() . ':langcode=en|entity.view.edit_form:view=test_view_block:location=block&name=test_view_block&display_id=block_1&langcode=en';
    $cached_id_token = Crypt::hmacBase64($cached_id, Settings::getHashSalt() . $this->container->get('private_key')->get());
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:assertContextualLinkPlaceHolder()
    // Check existence of the contextual link placeholders.
    $this->assertSession()->responseContains('<div' . new Attribute(['data-contextual-id' => $id, 'data-contextual-token' => $id_token, 'data-drupal-ajax-container' => '']) . '></div>');
    $this->assertSession()->responseContains('<div' . new Attribute(['data-contextual-id' => $cached_id, 'data-contextual-token' => $cached_id_token, 'data-drupal-ajax-container' => '']) . '></div>');

    // Get server-rendered contextual links.
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:renderContextualLinks()
    $post = ['ids[0]' => $id, 'ids[1]' => $cached_id, 'tokens[0]' => $id_token, 'tokens[1]' => $cached_id_token];
    $url = 'contextual/render?_format=json,destination=test-page';
    $this->getSession()->getDriver()->getClient()->request('POST', $url, $post);
    $this->assertSession()->statusCodeEquals(200);
    $json = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertSame('<ul class="contextual-links"><li><a href="' . base_path() . 'admin/structure/block/manage/' . $block->id() . '">Configure block</a></li><li><a href="' . base_path() . 'admin/structure/block/manage/' . $block->id() . '/delete">Remove block</a></li><li><a href="' . base_path() . 'admin/structure/views/view/test_view_block/edit/block_1">Edit view</a></li></ul>', $json[$id]);
    $this->assertSame('<ul class="contextual-links"><li><a href="' . base_path() . 'admin/structure/block/manage/' . $cached_block->id() . '">Configure block</a></li><li><a href="' . base_path() . 'admin/structure/block/manage/' . $cached_block->id() . '/delete">Remove block</a></li><li><a href="' . base_path() . 'admin/structure/views/view/test_view_block/edit/block_1">Edit view</a></li></ul>', $json[$cached_id]);
  }

}
