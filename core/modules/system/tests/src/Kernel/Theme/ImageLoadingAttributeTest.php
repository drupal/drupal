<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests lazy loading for images.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class ImageLoadingAttributeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'image_lazy_load_test'];

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
