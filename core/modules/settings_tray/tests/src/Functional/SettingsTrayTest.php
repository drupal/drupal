<?php

namespace Drupal\Tests\settings_tray\Functional;

use Drupal\block\Entity\Block;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests opening and saving block forms in the off-canvas dialog.
 *
 * @group settings_tray
 */
class SettingsTrayTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'settings_tray',
    'settings_tray_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Gets the block CSS selector.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block.
   *
   * @return string
   *   The CSS selector.
   */
  protected function getBlockSelector(Block $block) {
    return '#block-' . $block->id();
  }

  /**
   * Tests the 3 possible forms[settings_tray] annotations: class, FALSE, none.
   *
   * There is also functional JS test coverage to ensure that the two blocks
   * that support Settings Tray (the "class" and "none" cases) do work
   * correctly.
   *
   * @see SettingsTrayBlockFormTest::testBlocks()
   */
  public function testPossibleAnnotations() {
    $test_block_plugin_ids = [
      // Block that explicitly provides an "settings_tray" form class.
      'settings_tray_test_class',
      // Block that explicitly provides no "settings_tray" form, thus opting out.
      'settings_tray_test_false',
      // Block that does nothing explicit for Settings Tray.
      'settings_tray_test_none',
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

      // All blocks except 'settings_tray_test_false' are editable. For more
      // detailed test coverage, which requires JS execution, see
      // \Drupal\Tests\settings_tray\FunctionalJavascript\SettingsTrayBlockFormTest::testBlocks().
      if ($plugin_id === 'settings_tray_test_false') {
        $web_assert->elementNotExists('css', "{$block_selector}[data-drupal-settingstray=\"editable\"]");
      }
      else {
        $web_assert->elementExists('css', "{$block_selector}[data-drupal-settingstray=\"editable\"]");
      }
    }
  }

  /**
   * Tests that certain blocks opt out from Settings Tray.
   */
  public function testOptOut() {
    $web_assert = $this->assertSession();

    $non_excluded_block = $this->placeBlock('system_powered_by_block');
    $excluded_block_plugin_ids = ['page_title_block', 'system_main_block', 'settings_tray_test_false'];
    $block_selectors = [];
    // Place blocks that should be excluded.
    foreach ($excluded_block_plugin_ids as $excluded_block_plugin_id) {
      // The block HTML 'id' attribute will be "block-[block_id]".
      $block_selectors[] = $this->getBlockSelector($this->placeBlock($excluded_block_plugin_id));
    }
    $this->drupalGet('');
    // Assert that block has been marked as "editable" and contextual that
    // should exist does.
    $web_assert->elementExists('css', $this->getBlockSelector($non_excluded_block) . "[data-drupal-settingstray=\"editable\"]");
    // Assert that each block that has a "forms[settings_tray] = FALSE" annotation:
    // - is still rendered on the page
    // - but is not marked as "editable" by settings_tray_preprocess_block()
    // - and does not have the Settings Tray contextual link.
    foreach ($block_selectors as $block_selector) {
      $web_assert->elementExists('css', $block_selector);
      $web_assert->elementNotExists('css', "{$block_selector}[data-drupal-settingstray=\"editable\"]");
      $web_assert->elementNotExists('css', "$block_selector [data-settings-tray-edit]");
    }
  }

}
