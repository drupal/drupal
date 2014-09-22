<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\ImageTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests built-in image theme functions.
 *
 * @group Theme
 */
class ImageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system');

  /**
   * Tests that an image with the sizes attribute is output correctly.
   */
  function testThemeImageWithSizes() {
    // Test with multipliers.
    $sizes = '(max-width: ' . rand(10, 30) . 'em) 100vw, (max-width: ' . rand(30, 50) . 'em) 50vw, 30vw';
    $image = array(
      '#theme' => 'image',
      '#sizes' => $sizes,
      '#uri' => '/core/misc/druplicon.png',
      '#width' => rand(0, 1000) . 'px',
      '#height' => rand(0, 500) . 'px',
      '#alt' => $this->randomMachineName(),
      '#title' => $this->randomMachineName(),
    );
    $this->render($image);

    // Make sure sizes is set.
    $this->assertRaw($sizes, 'Sizes is set correctly.');
  }

}
