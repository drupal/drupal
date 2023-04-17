<?php

namespace Drupal\Tests\block_content\Functional\Views;

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
  protected static $modules = ['block', 'block_content', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the redirect destination when editing block content.
   */
  public function testRedirectDestination() {
    $this->drupalLogin($this->drupalCreateUser(['access block library', 'administer block content']));
    $this->drupalGet('admin/content/block');

    // Create a content block.
    $this->clickLink('content block');
    $edit = [];
    $edit['info[0][value]'] = 'Test redirect destination';
    $edit['body[0][value]'] = $this->randomMachineName(16);
    $this->submitForm($edit, 'Save');

    // Check the block content is present in the view redirect destination.
    $this->drupalGet('admin/content/redirect_destination');
    $this->assertSession()->pageTextContains('Test redirect destination');

    // Edit the created block and save.
    $this->clickLink('Edit');
    $this->submitForm([], 'Save');
    $this->assertSession()->addressEquals('admin/content/redirect_destination');
  }

}
