<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Render;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests selecting a display variant.
 */
#[Group('Render')]
#[RunTestsInSeparateProcesses]
class DisplayVariantTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['display_variant_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests selecting the variant and passing configuration.
   */
  public function testPageDisplayVariantSelectionEvent(): void {
    // Tests that our display variant was selected, and that its configuration
    // was passed correctly. If the configuration wasn't passed, we'd get an
    // error page here.
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('A very important, required value.');
    $this->assertSession()->pageTextContains('Explicitly passed in context.');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'custom_cache_tag');
  }

}
