<?php

/**
 * @file
 * Contains \Drupal\custom_block\Tests\CustomBlockLoadHooksTest.
 */

namespace Drupal\custom_block\Tests;

/**
 * Tests for the hooks invoked during custom_block_load().
 */
class CustomBlockLoadHooksTest extends CustomBlockTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('custom_block_test');

  /**
   * Declares test information.
   */
  public static function getInfo() {
    return array(
      'name' => 'Custom Block load hooks',
      'description' => 'Test the hooks invoked when a custom block is being loaded.',
      'group' => 'Custom Block',
    );
  }

  /**
   * Tests that hook_custom_block_load() is invoked correctly.
   */
  public function testHookCustomBlockLoad() {
    $other_bundle = $this->createCustomBlockType('other');
    // Create some sample articles and pages.
    $custom_block1 = $this->createCustomBlock();
    $custom_block2 = $this->createCustomBlock();
    $custom_block3 = $this->createCustomBlock();
    $custom_block4 = $this->createCustomBlock(FALSE, $other_bundle->id());

    // Check that when a set of custom blocks that only contains basic blocks is
    // loaded, the properties added to the custom block by
    // custom_block_test_load_custom_block() correctly reflect the expected
    // values.
    $custom_blocks = entity_load_multiple_by_properties('custom_block', array('type' => 'basic'));
    $loaded_custom_block = end($custom_blocks);
    $this->assertEqual($loaded_custom_block->custom_block_test_loaded_ids, array(
      $custom_block1->id->value,
      $custom_block2->id->value,
      $custom_block3->id->value
    ), 'hook_custom_block_load() received the correct list of custom_block IDs the first time it was called.');
    $this->assertEqual($loaded_custom_block->custom_block_test_loaded_types, array('basic'), 'hook_custom_block_load() received the correct list of custom block types the first time it was called.');

    // Now, as part of the same page request, load a set of custom_blocks that contain
    // both basic and other bundle, and make sure the parameters passed to
    // custom_block_test_custom_block_load() are correctly updated.
    $custom_blocks = entity_load_multiple('custom_block', \Drupal::entityQuery('custom_block')->execute(), TRUE);
    $loaded_custom_block = end($custom_blocks);
    $this->assertEqual($loaded_custom_block->custom_block_test_loaded_ids, array(
      $custom_block1->id->value,
      $custom_block2->id->value,
      $custom_block3->id->value,
      $custom_block4->id->value
    ), 'hook_custom_block_load() received the correct list of custom_block IDs the second time it was called.');
    $this->assertEqual($loaded_custom_block->custom_block_test_loaded_types, array('basic', 'other'), 'hook_custom_block_load() received the correct list of custom_block types the second time it was called.');
  }
}
