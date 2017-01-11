<?php

namespace Drupal\block_content\Tests\Views;

/**
 * Tests the redirect destination on block content on entity operations.
 *
 * @group block_content
 */
class BlockContentRedirectTest extends BlockContentTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_block_content_redirect_destination'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'block_content', 'views');

  /**
   * Tests the redirect destination when editing block content.
   */
  public function testRedirectDestination() {
    $this->drupalLogin($this->drupalCreateUser(array('administer blocks')));
    $this->drupalGet('admin/structure/block/block-content');

    // Create a custom block.
    $this->clickLink('custom block');
    $edit = array();
    $edit['info[0][value]'] = 'Test redirect destination';
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->drupalPostForm(NULL, $edit, 'Save');

    // Check the block content is present in the view redirect destination.
    $this->drupalGet('admin/content/redirect_destination');
    $this->assertText('Test redirect destination');

    // Edit the created block and save.
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, [], 'Save');
    $this->assertUrl('admin/content/redirect_destination');
  }

}
