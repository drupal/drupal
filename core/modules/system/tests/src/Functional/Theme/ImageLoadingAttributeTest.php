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
    $this->drupalGet('image-lazy-load-test');
    $this->assertSession()->responseContains('loading="lazy"');
  }

}
