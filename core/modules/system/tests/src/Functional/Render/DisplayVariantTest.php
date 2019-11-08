<?php

namespace Drupal\Tests\system\Functional\Render;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests selecting a display variant.
 *
 * @group Render
 */
class DisplayVariantTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['display_variant_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests selecting the variant and passing configuration.
   */
  public function testPageDisplayVariantSelectionEvent() {
    // Tests that our display variant was selected, and that its configuration
    // was passed correctly. If the configuration wasn't passed, we'd get an
    // error page here.
    $this->drupalGet('<front>');
    $this->assertRaw('A very important, required value.');
    $this->assertRaw('Explicitly passed in context.');
    $this->assertCacheTag('custom_cache_tag');
  }

}
