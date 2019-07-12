<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;
use Twig\TemplateWrapper;

/**
 * Tests Twig registry loader.
 *
 * @group Theme
 */
class TwigRegistryLoaderTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['twig_theme_test', 'block'];

  /**
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  protected function setUp() {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['test_theme_twig_registry_loader', 'test_theme_twig_registry_loader_theme', 'test_theme_twig_registry_loader_subtheme']);
    $this->twig = \Drupal::service('twig');
  }

  /**
   * Checks to see if a value is a Twig template.
   */
  public function assertTwigTemplate($value, $message = '', $group = 'Other') {
    $this->assertTrue($value instanceof TemplateWrapper, $message, $group);
  }

  /**
   * Tests template discovery using the Drupal theme registry.
   */
  public function testTemplateDiscovery() {
    $this->assertTwigTemplate($this->twig->load('block.html.twig'), 'Found block.html.twig in block module.');
  }

  /**
   * Tests template extension and includes using the Drupal theme registry.
   */
  public function testTwigNamespaces() {
    // Test the module-provided extend and insert templates.
    $this->drupalGet('twig-theme-test/registry-loader');
    $this->assertText('This line is from twig_theme_test/templates/twig-registry-loader-test-extend.html.twig');
    $this->assertText('This line is from twig_theme_test/templates/twig-registry-loader-test-include.html.twig');

    // Enable a theme that overrides the extend and insert templates to ensure
    // they are picked up by the registry loader.
    $this->config('system.theme')
      ->set('default', 'test_theme_twig_registry_loader')
      ->save();
    $this->drupalGet('twig-theme-test/registry-loader');
    $this->assertText('This line is from test_theme_twig_registry_loader/templates/twig-registry-loader-test-extend.html.twig');
    $this->assertText('This line is from test_theme_twig_registry_loader/templates/twig-registry-loader-test-include.html.twig');

    // Enable overriding theme that overrides the extend and insert templates
    // from the base theme.
    $this->config('system.theme')
      ->set('default', 'test_theme_twig_registry_loader_theme')
      ->save();
    $this->drupalGet('twig-theme-test/registry-loader');
    $this->assertText('This line is from test_theme_twig_registry_loader_theme/templates/twig-registry-loader-test-extend.html.twig');
    $this->assertText('This line is from test_theme_twig_registry_loader_theme/templates/twig-registry-loader-test-include.html.twig');

    // Enable a subtheme for the theme that doesn't have any overrides to make
    // sure that templates are being loaded from the first parent which has the
    // templates.
    $this->config('system.theme')
      ->set('default', 'test_theme_twig_registry_loader_subtheme')
      ->save();
    $this->drupalGet('twig-theme-test/registry-loader');
    $this->assertText('This line is from test_theme_twig_registry_loader_theme/templates/twig-registry-loader-test-extend.html.twig');
    $this->assertText('This line is from test_theme_twig_registry_loader_theme/templates/twig-registry-loader-test-include.html.twig');
  }

}
