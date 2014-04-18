<?php

/**
 * @file
 * Contains \Drupal\block\Tests\Views\DisplayBlockTest.
 */

namespace Drupal\block\Tests\Views;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\String;
use Drupal\views\Views;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\Core\Template\Attribute;

/**
 * Defines a test for block display.
 *
 * @see \Drupal\block\Plugin\views\display\Block
 */
class DisplayBlockTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'block_test_views', 'test_page_test', 'contextual', 'views_ui');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view_block', 'test_view_block2');

  public static function getInfo() {
    return array(
      'name' => ' Display: Block',
      'description' => 'Tests the block display plugin.',
      'group' => 'Views module integration',
    );
  }

  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), array('block_test_views'));
    $this->enableViewsTestModule();
  }

  /**
   * Tests default and custom block categories.
   */
  public function testBlockCategory() {
    $this->drupalLogin($this->drupalCreateUser(array('administer views', 'administer blocks')));

    // Create a new view in the UI.
    $edit = array();
    $edit['label'] = $this->randomString();
    $edit['id'] = strtolower($this->randomName());
    $edit['show[wizard_key]'] = 'standard:views_test_data';
    $edit['description'] = $this->randomString();
    $edit['block[create]'] = TRUE;
    $edit['block[style][row_plugin]'] = 'fields';
    $this->drupalPostForm('admin/structure/views/add', $edit, t('Save and edit'));

    // Test that the block was given a default category corresponding to its
    // base table.
    $arguments = array(
      ':id' => 'edit-category-lists-views',
      ':li_class' => 'views-block' . drupal_html_class($edit['id']) . '-block-1',
      ':href' => url('admin/structure/block/add/views_block:' . $edit['id'] . '-block_1/stark'),
      ':text' => $edit['label'],
    );
    $this->drupalGet('admin/structure/block');
    $elements = $this->xpath('//details[@id=:id]//li[contains(@class, :li_class)]/a[contains(@href, :href) and text()=:text]', $arguments);
    $this->assertTrue(!empty($elements), 'The test block appears in the category for its base table.');

    // Clone the block before changing the category.
    $this->drupalPostForm('admin/structure/views/view/' . $edit['id'] . '/edit/block_1', array(), t('Clone @display_title', array('@display_title' => 'Block')));
    $this->assertUrl('admin/structure/views/view/' . $edit['id'] . '/edit/block_2');

    // Change the block category to a random string.
    $this->drupalGet('admin/structure/views/view/' . $edit['id'] . '/edit/block_1');
    $label = t('Lists (Views)');
    $link = $this->xpath('//a[@id="views-block-1-block-category" and normalize-space(text())=:label]', array(':label' => $label));
    $this->assertTrue(!empty($link));
    $this->clickLink($label);
    $category = $this->randomString();
    $this->drupalPostForm(NULL, array('block_category' => $category), t('Apply'));

    // Clone the block after changing the category.
    $this->drupalPostForm(NULL, array(), t('Clone @display_title', array('@display_title' => 'Block')));
    $this->assertUrl('admin/structure/views/view/' . $edit['id'] . '/edit/block_3');

    $this->drupalPostForm(NULL, array(), t('Save'));

    // Test that the blocks are listed under the correct categories.
    $category_id = drupal_html_id('edit-category-' . String::checkPlain($category));
    $arguments[':id'] = $category_id;
    $this->drupalGet('admin/structure/block');
    $elements = $this->xpath('//details[@id=:id]//li[contains(@class, :li_class)]/a[contains(@href, :href) and text()=:text]', $arguments);
    $this->assertTrue(!empty($elements), 'The test block appears in the custom category.');

    $arguments = array(
      ':id' => 'edit-category-lists-views',
      ':li_class' => 'views-block' . drupal_html_class($edit['id']) . '-block-2',
      ':href' => url('admin/structure/block/add/views_block:' . $edit['id'] . '-block_2/stark'),
      ':text' => $edit['label'],
    );
    $elements = $this->xpath('//details[@id=:id]//li[contains(@class, :li_class)]/a[contains(@href, :href) and text()=:text]', $arguments);
    $this->assertTrue(!empty($elements), 'The first cloned test block remains in the original category.');

    $arguments = array(
      ':id' => $category_id,
      ':li_class' => 'views-block' . drupal_html_class($edit['id']) . '-block-3',
      ':href' => url('admin/structure/block/add/views_block:' . $edit['id'] . '-block_3/stark'),
      ':text' => $edit['label'],
    );
    $elements = $this->xpath('//details[@id=:id]//li[contains(@class, :li_class)]/a[contains(@href, :href) and text()=:text]', $arguments);
    $this->assertTrue(!empty($elements), 'The second cloned test block appears in the custom category.');
  }

  /**
   * Tests removing a block display.
   */
  protected function testDeleteBlockDisplay() {
    // To test all combinations possible we first place create two instances
    // of the block display of the first view.
    $block_1 = $this->drupalPlaceBlock('views_block:test_view_block-block_1', array('title' => 'test_view_block-block_1:1'));
    $block_2 = $this->drupalPlaceBlock('views_block:test_view_block-block_1', array('title' => 'test_view_block-block_1:2'));

    // Then we add one instance of blocks for each of the two displays of the
    // second view.
    $block_3 = $this->drupalPlaceBlock('views_block:test_view_block2-block_1', array('title' => 'test_view_block2-block_1'));
    $block_4 = $this->drupalPlaceBlock('views_block:test_view_block2-block_2', array('title' => 'test_view_block2-block_2'));

    $this->drupalGet('test-page');
    $this->assertBlockAppears($block_1);
    $this->assertBlockAppears($block_2);
    $this->assertBlockAppears($block_3);
    $this->assertBlockAppears($block_4);

    $block_storage = $this->container->get('entity.manager')->getStorage('block');

    // Remove the block display, so both block entities from the first view
    // should both disappear.
    $view = Views::getView('test_view_block');
    $view->initDisplay();
    $view->displayHandlers->remove('block_1');
    $view->storage->save();

    $this->assertFalse($block_storage->load($block_1->id()), 'The block for this display was removed.');
    $this->assertFalse($block_storage->load($block_2->id()), 'The block for this display was removed.');
    $this->assertTrue($block_storage->load($block_3->id()), 'A block from another view was unaffected.');
    $this->assertTrue($block_storage->load($block_4->id()), 'A block from another view was unaffected.');
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

    $this->assertFalse($block_storage->load($block_3->id()), 'The block for this display was removed.');
    $this->assertTrue($block_storage->load($block_4->id()), 'A block from another display on the same view was unaffected.');
    $this->drupalGet('test-page');
    $this->assertNoBlockAppears($block_3);
    $this->assertBlockAppears($block_4);
  }

  /**
   * Test the block form for a Views block.
   */
  public function testViewsBlockForm() {
    $this->drupalLogin($this->drupalCreateUser(array('administer blocks')));
    $default_theme = \Drupal::config('system.theme')->get('default');
    $this->drupalGet('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme);
    $elements = $this->xpath('//input[@name="label"]');
    $this->assertTrue(empty($elements), 'The label field is not found for Views blocks.');
    // Test that that machine name field is hidden from display and has been
    // saved as expected from the default value.
    $this->assertNoFieldById('edit-machine-name', 'views_block__test_view_block_1', 'The machine name is hidden on the views block form.');
    // Save the block.
    $this->drupalPostForm(NULL, array(), t('Save block'));
    $storage = $this->container->get('entity.manager')->getStorage('block');
    $block = $storage->load('views_block__test_view_block_block_1');
    // This will only return a result if our new block has been created with the
    // expected machine name.
    $this->assertTrue(!empty($block), 'The expected block was loaded.');

    for ($i = 2; $i <= 3; $i++) {
      // Place the same block again and make sure we have a new ID.
      $this->drupalPostForm('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme, array(), t('Save block'));
      $block = $storage->load('views_block__test_view_block_block_1_' . $i);
      // This will only return a result if our new block has been created with the
      // expected machine name.
      $this->assertTrue(!empty($block), 'The expected block was loaded.');
    }

    // Tests the override capability of items per page.
    $this->drupalGet('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme);
    $edit = array();
    $edit['settings[override][items_per_page]'] = 10;

    $this->drupalPostForm('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme, $edit, t('Save block'));

    $block = $storage->load('views_block__test_view_block_block_1_4');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEqual(10, $config['items_per_page'], "'Items per page' is properly saved.");

    $edit['settings[override][items_per_page]'] = 5;
    $this->drupalPostForm('admin/structure/block/manage/views_block__test_view_block_block_1_4', $edit, t('Save block'));

    $block = $storage->load('views_block__test_view_block_block_1_4');

    $config = $block->getPlugin()->getConfiguration();
    $this->assertEqual(5, $config['items_per_page'], "'Items per page' is properly saved.");

    // Tests the override of the label capability.
    $edit = array();
    $edit['settings[views_label_checkbox]'] = 1;
    $edit['settings[views_label]'] = 'Custom title';
    $this->drupalPostForm('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme, $edit, t('Save block'));

    $block = $storage->load('views_block__test_view_block_block_1_5');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEqual('Custom title', $config['views_label'], "'Label' is properly saved.");
  }

  /**
   * Tests the actual rendering of the views block.
   */
  public function testBlockRendering() {
    // Create a block and set a custom title.
    $block = $this->drupalPlaceBlock('views_block:test_view_block-block_1', array('title' => 'test_view_block-block_1:1', 'views_label' => 'Custom title'));
    $this->drupalGet('');

    $result = $this->xpath('//div[contains(@class, "region-sidebar-first")]/div[contains(@class, "block-views")]/h2');
    $this->assertEqual((string) $result[0], 'Custom title');

    // Don't override the title anymore.
    $plugin = $block->getPlugin();
    $plugin->setConfigurationValue('views_label', '');
    $block->save();

    $this->drupalGet('');
    $result = $this->xpath('//div[contains(@class, "region-sidebar-first")]/div[contains(@class, "block-views")]/h2');
    $this->assertEqual((string) $result[0], 'test_view_block');

    // Hide the title.
    $block->getPlugin()->setConfigurationValue('label_display', FALSE);
    $block->save();

    $this->drupalGet('');
    $result = $this->xpath('//div[contains(@class, "region-sidebar-first")]/div[contains(@class, "block-views")]/h2');
    $this->assertTrue(empty($result), 'The title is not visible.');
  }

  /**
   * Tests the contextual links on a Views block.
   */
  public function testBlockContextualLinks() {
    $this->drupalLogin($this->drupalCreateUser(array('administer views', 'access contextual links', 'administer blocks')));
    $block = $this->drupalPlaceBlock('views_block:test_view_block-block_1');
    $cached_block = $this->drupalPlaceBlock('views_block:test_view_block-block_1', array('cache' => array('max_age' => 3600)));
    $this->drupalGet('test-page');

    $id = 'block:block=' . $block->id() . ':|views_ui_edit:view=test_view_block:location=block&name=test_view_block&display_id=block_1';
    $cached_id = 'block:block=' . $cached_block->id() . ':|views_ui_edit:view=test_view_block:location=block&name=test_view_block&display_id=block_1';
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:assertContextualLinkPlaceHolder()
    $this->assertRaw('<div' . new Attribute(array('data-contextual-id' => $id)) . '></div>', format_string('Contextual link placeholder with id @id exists.', array('@id' => $id)));
    $this->assertRaw('<div' . new Attribute(array('data-contextual-id' => $cached_id)) . '></div>', format_string('Contextual link placeholder with id @id exists.', array('@id' => $cached_id)));

    // Get server-rendered contextual links.
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:renderContextualLinks()
    $post = array('ids[0]' => $id, 'ids[1]' => $cached_id);
    $response = $this->drupalPost('contextual/render', 'application/json', $post, array('query' => array('destination' => 'test-page')));
    $this->assertResponse(200);
    $json = Json::decode($response);
    $this->assertIdentical($json[$id], '<ul class="contextual-links"><li class="block-configure"><a href="' . base_path() . 'admin/structure/block/manage/' . $block->id() . '">Configure block</a></li><li class="views-uiedit"><a href="' . base_path() . 'admin/structure/views/view/test_view_block/edit/block_1">Edit view</a></li></ul>');
    $this->assertIdentical($json[$cached_id], '<ul class="contextual-links"><li class="block-configure"><a href="' . base_path() . 'admin/structure/block/manage/' . $cached_block->id() . '">Configure block</a></li><li class="views-uiedit"><a href="' . base_path() . 'admin/structure/views/view/test_view_block/edit/block_1">Edit view</a></li></ul>');
  }

}
