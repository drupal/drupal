<?php

namespace Drupal\Tests\outside_in\Functional;

use Drupal\block\Entity\Block;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests opening and saving block forms in the off-canvas dialog.
 *
 * @group outside_in
 */
class OutsideInTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'outside_in',
    'outside_in_test',
  ];

  /**
   * Gets the block CSS selector.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block.
   *
   * @return string
   *   The CSS selector.
   */
  protected  function getBlockSelector(Block $block) {
    return '#block-' . $block->id();
  }

  /**
   * Tests the three possible forms[off_canvas] annotations: class, FALSE, none.
   *
   * There is also functional JS test coverage to ensure that the two blocks
   * that support Settings Tray (the "class" and "none" cases) do work
   * correctly.
   *
   * @see OutsideInBlockFormTest::testBlocks()
   */
  public function testPossibleAnnotations() {
    $test_block_plugin_ids = [
      // Block that explicitly provides an "off_canvas" form class.
      'outside_in_test_class',
      // Block that explicitly provides no "off_canvas" form, thus opting out.
      'outside_in_test_false',
      // Block that does nothing explicit for Settings Tray.
      'outside_in_test_none',
    ];

    $placed_blocks = [];
    foreach ($test_block_plugin_ids as $plugin_id) {
      $placed_blocks[$plugin_id] = $this->placeBlock($plugin_id);
    }

    $this->drupalGet('');
    $web_assert = $this->assertSession();
    foreach ($placed_blocks as $plugin_id => $placed_block) {
      $block_selector = $this->getBlockSelector($placed_block);

      // All blocks are rendered.
      $web_assert->elementExists('css', $block_selector);

      // All blocks except 'outside_in_test_false' are editable. For more
      // detailed test coverage, which requires JS execution, see
      // \Drupal\Tests\outside_in\FunctionalJavascript\OutsideInBlockFormTest::testBlocks().
      if ($plugin_id === 'outside_in_test_false') {
        $web_assert->elementNotExists('css', "{$block_selector}[data-drupal-outsidein=\"editable\"]");
      }
      else {
        $web_assert->elementExists('css', "{$block_selector}[data-drupal-outsidein=\"editable\"]");
      }
    }
  }

  /**
   * Tests that certain blocks opt out from Settings Tray.
   */
  public function testOptOut() {
    $web_assert = $this->assertSession();

    $non_excluded_block = $this->placeBlock('system_powered_by_block');
    $excluded_block_plugin_ids = ['page_title_block', 'system_main_block', 'outside_in_test_false'];
    $block_selectors = [];
    // Place blocks that should be excluded.
    foreach ($excluded_block_plugin_ids as $excluded_block_plugin_id) {
      // The block HTML 'id' attribute will be "block-[block_id]".
      $block_selectors[] = $this->getBlockSelector($this->placeBlock($excluded_block_plugin_id));
    }
    $this->drupalGet('');
    // Assert that block has been marked as "editable" and contextual that
    // should exist does.
    $web_assert->elementExists('css', $this->getBlockSelector($non_excluded_block) . "[data-drupal-outsidein=\"editable\"]");
    // Assert that each block that has a "forms[off_canvas] = FALSE" annotation:
    // - is still rendered on the page
    // - but is not marked as "editable" by outside_in_preprocess_block()
    // - and does not have the Settings Tray contextual link.
    foreach ($block_selectors as $block_selector) {
      $web_assert->elementExists('css', $block_selector);
      $web_assert->elementNotExists('css', "{$block_selector}[data-drupal-outsidein=\"editable\"]");
      $web_assert->elementNotExists('css', "$block_selector [data-outside-in-edit]");
    }
  }

}
