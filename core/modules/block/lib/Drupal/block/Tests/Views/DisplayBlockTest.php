<?php

/**
 * @file
 * Contains \Drupal\block\Tests\Views\DisplayBlockTest.
 */

namespace Drupal\block\Tests\Views;

use Drupal\block\Plugin\Core\Entity\Block;
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
  public static $modules = array('block_test_views', 'test_page_test');

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
      'group' => 'Views Modules',
    );
  }

  protected function setUp() {
    parent::setUp();

    ViewTestData::importTestViews(get_class($this), array('block_test_views'));
    $this->enableViewsTestModule();
  }

  /**
   * Checks to see whether a block appears on the page.
   *
   * @param \Drupal\block\Plugin\Core\Entity\Block $block
   *   The block entity to find on the page.
   */
  protected function assertBlockAppears(Block $block) {
    $result = $this->findBlockInstance($block);
    $this->assertTrue(!empty($result), format_string('Ensure the block @id appears on the page', array('@id' => $block->id())));
  }

  /**
   * Checks to see whether a block does not appears on the page.
   *
   * @param \Drupal\block\Plugin\Core\Entity\Block $block
   *   The block entity to find on the page.
   */
  protected function assertNoBlockAppears(Block $block) {
    $result = $this->findBlockInstance($block);
    $this->assertFalse(!empty($result), format_string('Ensure the block @id does not appear on the page', array('@id' => $block->id())));
  }

  /**
   * Find a block instance on the page.
   *
   * @param \Drupal\block\Plugin\Core\Entity\Block $block
   *   The block entity to find on the page.
   *
   * @return array
   *   The result from the xpath query.
   */
  protected function findBlockInstance(Block $block) {
    $config_id = explode('.', $block->id());
    $machine_name = array_pop($config_id);
    return $this->xpath('//div[@id = :id]', array(':id' => 'block-' . $machine_name));
  }

  /**
   * Tests removing a block display.
   */
  protected function testDeleteBlockDisplay() {
    // To test all combinations possible we first place create two instances
    // of the block display of the first view.
    $block_1 = $this->drupalPlaceBlock('views_block:test_view_block-block_1', array(), array('title' => 'test_view_block-block_1:1'));
    $block_2 = $this->drupalPlaceBlock('views_block:test_view_block-block_1', array(), array('title' => 'test_view_block-block_1:2'));

    // Then we add one instance of blocks for each of the two displays of the
    // second view.
    $block_3 = $this->drupalPlaceBlock('views_block:test_view_block2-block_1', array(), array('title' => 'test_view_block2-block_1'));
    $block_4 = $this->drupalPlaceBlock('views_block:test_view_block2-block_2', array(), array('title' => 'test_view_block2-block_2'));

    $this->drupalGet('test-page');
    $this->assertBlockAppears($block_1);
    $this->assertBlockAppears($block_2);
    $this->assertBlockAppears($block_3);
    $this->assertBlockAppears($block_4);

    $block_storage_controller = $this->container->get('plugin.manager.entity')->getStorageController('block');

    // Remove the block display, so both block entities from the first view
    // should both dissapear.
    $view = views_get_view('test_view_block');
    $view->initDisplay();
    $view->displayHandlers->remove('block_1');
    $view->storage->save();

    $this->assertFalse($block_storage_controller->load(array($block_1->id())), 'The block for this display was removed.');
    $this->assertFalse($block_storage_controller->load(array($block_2->id())), 'The block for this display was removed.');
    $this->assertTrue($block_storage_controller->load(array($block_3->id())), 'A block from another view was unaffected.');
    $this->assertTrue($block_storage_controller->load(array($block_4->id())), 'A block from another view was unaffected.');
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

    $this->assertFalse($block_storage_controller->load(array($block_3->id())), 'The block for this display was removed.');
    $this->assertTrue($block_storage_controller->load(array($block_4->id())), 'A block from another display on the same view was unaffected.');
    $this->drupalGet('test-page');
    $this->assertNoBlockAppears($block_3);
    $this->assertBlockAppears($block_4);
  }

  /**
   * Test the block form for a Views block.
   */
  public function testViewsBlockForm() {
    $this->drupalLogin($this->drupalCreateUser(array('administer blocks')));
    $default_theme = variable_get('theme_default', 'stark');
    $this->drupalGet('admin/structure/block/add/views_block:test_view_block-block_1/' . $default_theme);
    $elements = $this->xpath('//input[@name="label"]');
    $this->assertTrue(empty($elements), 'The label field is not found for Views blocks.');
  }

}
