<?php

namespace Drupal\Tests\block_content\Functional;

/**
 * Create a block and test block access by attempting to view the block.
 *
 * @group block_content
 */
class BlockContentPageViewTest extends BlockContentTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block_content_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Checks block edit and fallback functionality.
   */
  public function testPageEdit() {
    $this->drupalLogin($this->adminUser);
    $block = $this->createBlockContent();

    // Attempt to view the block.
    $this->drupalGet('block-content/' . $block->id());

    // Ensure user was able to view the block.
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('<front>');
    $this->assertRaw(t('This block is broken or missing. You may be missing content or you might need to enable the original module.'));
  }

}
