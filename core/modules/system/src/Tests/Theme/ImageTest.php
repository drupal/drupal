<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\ImageTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;

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

  /*
   * The images to test with.
   *
   * @var array
   */
  protected $testImages;

  protected function setUp() {
    parent::setUp();

    // The code under test uses file_url_transform_relative(), which relies on
    // the Request containing the correct hostname. KernelTestBase doesn't set
    // it, so push another request onto the stack to ensure it's correct.
    $request = Request::create('/', 'GET', [], [], [], $_SERVER);
    $this->container = $this->kernel->getContainer();
    $this->container->get('request_stack')->push($request);

    $this->testImages = array(
      'core/misc/druplicon.png',
      'core/misc/loading.gif',
    );
  }

  /**
   * Tests that an image with the sizes attribute is output correctly.
   */
  function testThemeImageWithSizes() {
    // Test with multipliers.
    $sizes = '(max-width: ' . rand(10, 30) . 'em) 100vw, (max-width: ' . rand(30, 50) . 'em) 50vw, 30vw';
    $image = array(
      '#theme' => 'image',
      '#sizes' => $sizes,
      '#uri' => reset($this->testImages),
      '#width' => rand(0, 1000) . 'px',
      '#height' => rand(0, 500) . 'px',
      '#alt' => $this->randomMachineName(),
      '#title' => $this->randomMachineName(),
    );
    $this->render($image);

    // Make sure sizes is set.
    $this->assertRaw($sizes, 'Sizes is set correctly.');
  }

  /**
   * Tests that an image with the src attribute is output correctly.
   */
  function testThemeImageWithSrc() {

    $image = array(
      '#theme' => 'image',
      '#uri' => reset($this->testImages),
      '#width' => rand(0, 1000) . 'px',
      '#height' => rand(0, 500) . 'px',
      '#alt' => $this->randomMachineName(),
      '#title' => $this->randomMachineName(),
    );
    $this->render($image);

    // Make sure the src attribute has the correct value.
    $this->assertRaw(file_url_transform_relative(file_create_url($image['#uri'])), 'Correct output for an image with the src attribute.');
  }

  /**
   * Tests that an image with the srcset and multipliers is output correctly.
   */
  function testThemeImageWithSrcsetMultiplier() {
    // Test with multipliers.
    $image = array(
      '#theme' => 'image',
      '#srcset' => array(
        array(
          'uri' => $this->testImages[0],
          'multiplier' => '1x',
        ),
        array(
          'uri' => $this->testImages[1],
          'multiplier' => '2x',
        ),
      ),
      '#width' => rand(0, 1000) . 'px',
      '#height' => rand(0, 500) . 'px',
      '#alt' => $this->randomMachineName(),
      '#title' => $this->randomMachineName(),
    );
    $this->render($image);

    // Make sure the srcset attribute has the correct value.
    $this->assertRaw(file_url_transform_relative(file_create_url($this->testImages[0])) . ' 1x, ' . file_url_transform_relative(file_create_url($this->testImages[1])) . ' 2x', 'Correct output for image with srcset attribute and multipliers.');
  }

  /**
   * Tests that an image with the srcset and widths is output correctly.
   */
  function testThemeImageWithSrcsetWidth() {
    // Test with multipliers.
    $widths = array(
      rand(0, 500) . 'w',
      rand(500, 1000) . 'w',
    );
    $image = array(
      '#theme' => 'image',
      '#srcset' => array(
        array(
          'uri' => $this->testImages[0],
          'width' => $widths[0],
        ),
        array(
          'uri' => $this->testImages[1],
          'width' => $widths[1],
        ),
      ),
      '#width' => rand(0, 1000) . 'px',
      '#height' => rand(0, 500) . 'px',
      '#alt' => $this->randomMachineName(),
      '#title' => $this->randomMachineName(),
    );
    $this->render($image);

    // Make sure the srcset attribute has the correct value.
    $this->assertRaw(file_url_transform_relative(file_create_url($this->testImages[0])) . ' ' . $widths[0] . ', ' . file_url_transform_relative(file_create_url($this->testImages[1])) . ' ' . $widths[1], 'Correct output for image with srcset attribute and width descriptors.');
  }

}
