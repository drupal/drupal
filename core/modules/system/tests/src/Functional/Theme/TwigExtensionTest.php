<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;
use Drupal\twig_extension_test\TwigExtension\TestExtension;

/**
 * Tests Twig extensions.
 *
 * @group Theme
 */
class TwigExtensionTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['theme_test', 'twig_extension_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['test_theme']);
  }

  /**
   * Tests that the provided Twig extension loads the service appropriately.
   */
  public function testTwigExtensionLoaded() {
    $twigService = \Drupal::service('twig');
    $ext = $twigService->getExtension(TestExtension::class);
    $this->assertInstanceOf(TestExtension::class, $ext);
  }

  /**
   * Tests that the Twig extension's filter produces expected output.
   */
  public function testTwigExtensionFilter() {
    $this->config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    $this->drupalGet('twig-extension-test/filter');
    $this->assertSession()->pageTextContains('Every plant is not a mineral.');
    // Test safe_join filter.
    $this->assertRaw('&lt;em&gt;will be escaped&lt;/em&gt;<br/><em>will be markup</em><br/><strong>will be rendered</strong>');
  }

  /**
   * Tests that the Twig extension's function produces expected output.
   */
  public function testTwigExtensionFunction() {
    $this->config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    $this->drupalGet('twig-extension-test/function');
    $this->assertSession()->pageTextContains('THE QUICK BROWN BOX JUMPS OVER THE LAZY DOG 123.');
    $this->assertSession()->pageTextContains('the quick brown box jumps over the lazy dog 123.');
    $this->assertSession()->pageTextNotContains('The Quick Brown Fox Jumps Over The Lazy Dog 123.');
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
    $this->assertSame(0, $extension->escapeFilter($twig, 0), 'TwigExtension::escapeFilter() returns zero correctly when provided as an integer.');
    $this->assertSame(0, $extension->escapeFilter($twig, 0.0), 'TwigExtension::escapeFilter() returns zero correctly when provided as a double.');
  }

  /**
   * Tests output of integer and double 0 values of TwigExtension->renderVar().
   *
   * @see https://www.drupal.org/node/2417733
   */
  public function testsRenderZeroValue() {
    /** @var \Drupal\Core\Template\TwigExtension $extension */
    $extension = \Drupal::service('twig.extension');
    $this->assertSame(0, $extension->renderVar(0), 'TwigExtension::renderVar() renders zero correctly when provided as an integer.');
    $this->assertSame(0, $extension->renderVar(0.0), 'TwigExtension::renderVar() renders zero correctly when provided as a double.');
  }

}
