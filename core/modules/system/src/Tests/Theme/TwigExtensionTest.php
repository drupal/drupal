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
    $this->config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    $this->drupalGet('twig-extension-test/filter');
    $this->assertText('Every plant is not a mineral.', 'Success: String filtered.');
  }

  /**
   * Tests that the Twig extension's function produces expected output.
   */
  function testTwigExtensionFunction() {
    $this->config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    $this->drupalGet('twig-extension-test/function');
    $this->assertText('THE QUICK BROWN BOX JUMPS OVER THE LAZY DOG 123.', 'Success: Text converted to uppercase.');
    $this->assertText('the quick brown box jumps over the lazy dog 123.', 'Success: Text converted to lowercase.');
    $this->assertNoText('The Quick Brown Fox Jumps Over The Lazy Dog 123.', 'Success: No text left behind.');
  }

  /**
   * Tests output of integer and double 0 values of TwigExtension::escapeFilter().
   *
   * @see https://www.drupal.org/node/2417733
   */
  public function testsRenderEscapedZeroValue() {
    /** @var \Drupal\Core\Template\TwigExtension $extension */
    $extension = \Drupal::service('twig.extension');
    /** @var \Drupal\Core\Template\TwigEnvironment $twig */
    $twig = \Drupal::service('twig');
    $this->assertIdentical($extension->escapeFilter($twig, 0), 0, 'TwigExtension::escapeFilter() returns zero correctly when provided as an integer.');
    $this->assertIdentical($extension->escapeFilter($twig, 0.0), 0, 'TwigExtension::escapeFilter() returns zero correctly when provided as a double.');
  }

  /**
   * Tests output of integer and double 0 values of TwigExtension->renderVar().
   *
   * @see https://www.drupal.org/node/2417733
   */
  public function testsRenderZeroValue() {
    /** @var \Drupal\Core\Template\TwigExtension $extension */
    $extension = \Drupal::service('twig.extension');
    $this->assertIdentical($extension->renderVar(0), 0, 'TwigExtension::renderVar() renders zero correctly when provided as an integer.');
    $this->assertIdentical($extension->renderVar(0.0), 0, 'TwigExtension::renderVar() renders zero correctly when provided as a double.');
  }

}
