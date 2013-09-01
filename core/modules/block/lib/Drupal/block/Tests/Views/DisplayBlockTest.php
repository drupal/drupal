<?php

/**
 * @file
 * Contains \Drupal\block\Tests\Views\DisplayBlockTest.
 */

namespace Drupal\block\Tests\Views;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

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
  public static $modules = array('block_test_views', 'test_page_test', 'contextual', 'views_ui');

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

    ViewTestData::importTestViews(get_class($this), array('block_test_views'));
    $this->enableViewsTestModule();
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

    $block_storage_controller = $this->container->get('entity.manager')->getStorageController('block');

    // Remove the block display, so both block entities from the first view
    // should both dissapear.
    $view = views_get_view('test_view_block');
    $view->initDisplay();
    $view->displayHandlers->remove('block_1');
    $view->storage->save();

    $this->assertFalse($block_storage_controller->load($block_1->id()), 'The block for this display was removed.');
    $this->assertFalse($block_storage_controller->load($block_2->id()), 'The block for this display was removed.');
    $this->assertTrue($block_storage_controller->load($block_3->id()), 'A block from another view was unaffected.');
    $this->assertTrue($block_storage_controller->load($block_4->id()), 'A block from another view was unaffected.');
    $this->drupalGet('test-page');
    $this->assertNoBlockAppears($block_1);
    $this->assertNoBlockAppears($block_2);
    $this->assertBlockAppears($block_3);
    $this->assertBlockAppears($block_4);

    // Remove the first block display of the second view and ensure the block
    // instance of the second block display still exists.
    $view = views_get_view('test_view_block2');
    $view->initDisplay();
    $view->displayHandlers->remove('block_1');
    $view->storage->save();

    $this->assertFalse($block_storage_controller->load($block_3->id()), 'The block for this display was removed.');
    $this->assertTrue($block_storage_controller->load($block_4->id()), 'A block from another display on the same view was unaffected.');
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
    $this->assertNoFieldById('edit-machine-name', 'stark.views_block__test_view_block_1', 'The machine name is hidden on the views block form.');
    // Save the block.
    $this->drupalPost(NULL, array(), t('Save block'));
    $storage = $this->container->get('entity.manager')->getStorageController('block');
    $block = $storage->load('stark.views_block__test_view_block_block_1');
    // This will only return a result if our new block has been created with the
    // expected machine name.
    $this->assertTrue(!empty($block), 'The expected block was loaded.');

    for ($i = 2; $i <= 3; $i++) {
      // Place the same block again and make sure we have a new ID.
      $this->drupalPost('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme, array(), t('Save block'));
      $block = $storage->load('stark.views_block__test_view_block_block_1_' . $i);
      // This will only return a result if our new block has been created with the
      // expected machine name.
      $this->assertTrue(!empty($block), 'The expected block was loaded.');
    }

    // Tests the override capability of items per page.
    $this->drupalGet('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme);
    $edit = array();
    $edit['settings[override][items_per_page]'] = 10;

    $this->drupalPost('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme, $edit, t('Save block'));

    $block = $storage->load('stark.views_block__test_view_block_block_1_4');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEqual(10, $config['items_per_page'], "'Items per page' is properly saved.");

    $edit['settings[override][items_per_page]'] = 5;
    $this->drupalPost('admin/structure/block/manage/stark.views_block__test_view_block_block_1_4', $edit, t('Save block'));

    $block = $storage->load('stark.views_block__test_view_block_block_1_4');

    $config = $block->getPlugin()->getConfiguration();
    $this->assertEqual(5, $config['items_per_page'], "'Items per page' is properly saved.");
  }

  /**
   * Tests the contextual links on a Views block.
   */
  public function testBlockContextualLinks() {
    $this->drupalLogin($this->drupalCreateUser(array('administer views', 'access contextual links', 'administer blocks')));
    $block = $this->drupalPlaceBlock('views_block:test_view_block-block_1');
    $this->drupalGet('test-page');

    $id = 'block:admin/structure/block/manage:' . $block->id() . ':|views_ui:admin/structure/views/view:test_view_block:location=block&name=test_view_block&display_id=block_1';
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:assertContextualLinkPlaceHolder()
    $this->assertRaw('<div data-contextual-id="'. $id . '"></div>', format_string('Contextual link placeholder with id @id exists.', array('@id' => $id)));

    // Get server-rendered contextual links.
    // @see \Drupal\contextual\Tests\ContextualDynamicContextTest:renderContextualLinks()
    $post = urlencode('ids[0]') . '=' . urlencode($id);
    $response = $this->curlExec(array(
      CURLOPT_URL => url('contextual/render', array('absolute' => TRUE, 'query' => array('destination' => 'test-page'))),
      CURLOPT_POST => TRUE,
      CURLOPT_POSTFIELDS => $post,
      CURLOPT_HTTPHEADER => array(
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
      ),
    ));
    $this->assertResponse(200);
    $json = drupal_json_decode($response);
    $this->assertIdentical($json[$id], '<ul class="contextual-links"><li class="block-configure odd first"><a href="' . base_path() . 'admin/structure/block/manage/' . $block->id() . '?destination=test-page">Configure block</a></li><li class="views-ui-edit even last"><a href="' . base_path() . 'admin/structure/views/view/test_view_block/edit/block_1?destination=test-page">Edit view</a></li></ul>');
  }

}
