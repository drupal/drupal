<?php

namespace Drupal\Tests\system\Functional\Theme;

use Behat\Mink\Exception\ElementHtmlException;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\WebAssert;

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
    $assert = $this->assertSession();

    // Get page under test.
    $this->drupalGet('image-lazy-load-test');

    // Loading attribute is added when image dimensions has been set.
    $assert->elementAttributeExists('css', '#with-dimensions img', 'loading');
    $assert->elementAttributeContains('css', '#with-dimensions img', 'loading', 'lazy');

    // Loading attribute with lazy default value can be overriden.
    $assert->elementAttributeContains('css', '#override-loading-attribute img', 'loading', 'eager');

    // Without image dimensions loading attribute is not generated.
    $this->assertFalse($this->elementAttributeExists($assert, 'css', '#without-dimensions img', 'loading'));
  }

  /**
   * Checks that an attribuet exists in an element.
   *
   * Exends Drupal\Tests\WebAssert::elementAttributeExists() method to returns a
   * boolean type instead throwing an execption when attribuet is not found.
   *
   * @param \Drupal\Tests\WebAssert $assert
   * @param string $selectorType
   * @param string|array $selector
   * @param string $attribute
   *
   * @see Drupal\Tests\WebAssert::elementAttributeExists()
   *
   * @return bool
   *   Returns TRUE if $attribute exists, FALSE otherwise.
   */
  protected function elementAttributeExists(WebAssert $assert, $selectorType, $selector, $attribute) {
    $attribute_exists = TRUE;
    try {
      $assert->elementAttributeExists($selectorType, $selector, $attribute);
    }
    catch (ElementHtmlException $th) {
      $attribute_exists = FALSE;
    }

    return $attribute_exists;
  }

}
