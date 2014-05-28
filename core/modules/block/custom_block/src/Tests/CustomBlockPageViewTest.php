<?php

/**
 * @file
 * Contains \Drupal\custom_block\Tests\CustomBlockPageViewTest.
 */

namespace Drupal\custom_block\Tests;

/**
 * Tests the block edit functionality.
 */
class CustomBlockPageViewTest extends CustomBlockTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'custom_block', 'custom_block_test');

  /**
   * Declares test information.
   */
  public static function getInfo() {
    return array(
      'name' => 'Custom Block page view',
      'description' => 'Create a block and test block access by attempting to view the block.',
      'group' => 'Custom Block',
    );
  }

  /**
   * Checks block edit functionality.
   */
  public function testPageEdit() {
    $this->drupalLogin($this->adminUser);
    $block = $this->createCustomBlock();

    // Attempt to view the block.
    $this->drupalGet('custom-block/' . $block->id());

    // Assert response was '200' and not '403 Access denied'.
    $this->assertResponse('200', 'User was able the view the block');
  }

}
