<?php

/**
 * @file
 * Contains \Drupal\custom_block\Tests\CustomBlockBuildContentTest.
 */

namespace Drupal\custom_block\Tests;

/**
 * Test to ensure that a block's content is always rebuilt.
 */
class CustomBlockBuildContentTest extends CustomBlockTestBase {

  /**
   * Declares test information.
   */
  public static function getInfo() {
    return array(
      'name' => 'Rebuild content',
      'description' => 'Test the rebuilding of content for full view modes.',
      'group' => 'Custom Block',
    );
  }

  /**
   * Ensures that content is rebuilt in calls to custom_block_build_content().
   */
  public function testCustomBlockRebuildContent() {
    $block = $this->createCustomBlock();

    // Set a property in the content array so we can test for its existence later on.
    $block->content['test_content_property'] = array(
      '#value' => $this->randomString(),
    );
    $content = entity_view_multiple(array($block), 'full');

    // If the property doesn't exist it means the block->content was rebuilt.
    $this->assertFalse(isset($content['test_content_property']), 'Custom block content was emptied prior to being built.');
  }
}
