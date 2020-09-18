<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests lazy loading for images.
 *
 * @group Theme
 */
class ImageLoadingAttributeTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['image_lazy_load_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that loading attribute is enabled for images.
   */
  public function testImageLoadingAttribute() {
    // Loading attribute is added when image dimensions has been set.
    $this->drupalGet('image-lazy-load-test');
    $this->assertSession()->elementAttributeExists('css', '#with-dimensions img', 'loading');
    $this->assertSession()->elementAttributeContains('css', '#with-dimensions img', 'loading', 'lazy');

    // Without image dimensions loading attribute is not generated.
    $this->assertSession()->elementAttributeContains('css', '#without-dimensions img', 'alt', 'Image lazy load testing image without dimensions');
    $this->expectExceptionMessage('The attribute "loading" was not found in the element matching css "#without-dimensions img".');
    $this->assertSession()->elementAttributeExists('css', '#without-dimensions img', 'loading');
  }

}
