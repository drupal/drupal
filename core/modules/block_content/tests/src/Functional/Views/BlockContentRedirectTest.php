<?php

declare(strict_types=1);

namespace Drupal\Tests\block_content\Functional\Views;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the redirect destination on block content on entity operations.
 */
#[Group('block_content')]
#[RunTestsInSeparateProcesses]
class BlockContentRedirectTest extends BlockContentTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_block_content_redirect_destination'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'block_content', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the redirect destination when editing block content.
   */
  public function testRedirectDestination(): void {
    $this->drupalLogin($this->drupalCreateUser(['access block library', 'administer block content']));

    // Create a content block.
    $block = $this->createBlockContent();

    // Check the block content is present in the view redirect destination.
    $this->drupalGet('admin/content/redirect_destination');
    $this->assertSession()->pageTextContains($block->label());

    // Edit the created block and save.
    $this->clickLink('Edit');
    $this->submitForm([], 'Save');
    $this->assertSession()->addressEquals('admin/content/redirect_destination');
  }

}
