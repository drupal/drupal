<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\TwigExtensionTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Twig extensions.
 *
 * @group Theme
 */
class TwigExtensionTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test', 'twig_extension_test');

  protected function setUp() {
    parent::setUp();
    \Drupal::service('theme_handler')->install(array('test_theme'));
  }

  /**
   * Tests that the provided Twig extension loads the service appropriately.
   */
  function testTwigExtensionLoaded() {
    $twigService = \Drupal::service('twig');
    $ext = $twigService->getExtension('twig_extension_test.test_extension');
    $this->assertEqual(get_class($ext), 'Drupal\twig_extension_test\TwigExtension\TestExtension', 'TestExtension loaded successfully.');
  }

  /**
   * Tests that the Twig extension's filter produces expected output.
   */
  function testTwigExtensionFilter() {
    \Drupal::config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    $this->drupalGet('twig-extension-test/filter');
    $this->assertText('Every plant is not a mineral.', 'Success: String filtered.');
  }

  /**
   * Tests that the Twig extension's function produces expected output.
   */
  function testTwigExtensionFunction() {
    \Drupal::config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    $this->drupalGet('twig-extension-test/function');
    $this->assertText('THE QUICK BROWN BOX JUMPS OVER THE LAZY DOG 123.', 'Success: Text converted to uppercase.');
    $this->assertText('the quick brown box jumps over the lazy dog 123.', 'Success: Text converted to lowercase.');
    $this->assertNoText('The Quick Brown Fox Jumps Over The Lazy Dog 123.', 'Success: No text left behind.');
  }

}
