<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests lazy loading for images.
 *
 * @group Theme
 */
class ImageLoadingAttributeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image_lazy_load_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that loading attribute is enabled for images.
   */
  public function testImageLoadingAttribute(): void {
    $assert = $this->assertSession();

    // Get page under test.
    $this->drupalGet('image-lazy-load-test');

    // Loading attribute is added when image dimensions has been set.
    $assert->elementAttributeExists('css', '#with-dimensions img', 'loading');
    $assert->elementAttributeContains('css', '#with-dimensions img', 'loading', 'lazy');

    // Loading attribute with lazy default value can be overridden.
    $assert->elementAttributeContains('css', '#override-loading-attribute img', 'loading', 'eager');

    // Without image dimensions loading attribute is not generated.
    $element = $assert->elementExists('css', '#without-dimensions img');
    $this->assertFalse($element->hasAttribute('loading'));
  }

}
